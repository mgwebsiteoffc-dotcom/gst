import Alpine from 'alpinejs';
import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['X-CSRF-TOKEN'] =
    document.querySelector('meta[name="csrf-token"]')?.content;

const inr = new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', minimumFractionDigits: 2 });
const round2 = (v) => Math.round((Number(v) + Number.EPSILON) * 100) / 100;

/** Indian-numbering amount in words — mirrors App\Support\Money::inWords(). */
function amountInWords(amount) {
    const ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
        'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
    const tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
    const two = (n) => (n < 20 ? ones[n] : `${tens[Math.floor(n / 10)]} ${ones[n % 10]}`.trim());

    const words = (n) => {
        if (n === 0) return 'Zero';
        const parts = [];
        for (const [div, label] of [[10000000, 'Crore'], [100000, 'Lakh'], [1000, 'Thousand'], [100, 'Hundred']]) {
            if (n >= div) {
                const count = Math.floor(n / div);
                parts.push(`${div >= 1000 ? words(count) : two(count)} ${label}`);
                n %= div;
            }
        }
        if (n > 0) parts.push(two(n));
        return parts.join(' ');
    };

    const rupees = Math.floor(amount);
    const paise = Math.round((amount - rupees) * 100);
    let out = `${words(rupees)} Rupees`;
    if (paise > 0) out += ` and ${words(paise)} Paise`;
    return `${out} Only`;
}

/**
 * Live invoice editor. Totals shown here mirror App\Services\Gst\TaxCalculator,
 * but the server always recalculates on save — this is a preview, never the source of truth.
 */
Alpine.data('invoiceEditor', (config = {}) => ({
    taxMode: config.taxMode ?? 'gst',
    sellerState: config.sellerState ?? null,
    placeOfSupply: config.placeOfSupply ?? null,
    partyId: config.partyId ?? '',
    partyQuery: '',
    partyOpen: false,
    parties: config.parties ?? {},
    catalog: config.catalog ?? [],
    lines: config.lines?.length ? config.lines : [],
    shipping: Number(config.shipping) || 0,
    roundOff: config.roundOff ?? true,
    isExport: config.isExport ?? false,
    isPurchase: config.isPurchase ?? false,
    issueDate: config.issueDate ?? '',
    dueDate: config.dueDate ?? '',

    init() {
        if (!this.lines.length) this.addLine();
        if (this.partyId) this.partyQuery = this.partyLabel(this.partyId);
        // Enter in the last row adds a new line instead of submitting the form.
        this.$el.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && e.target.matches('input:not([type=submit])')) {
                e.preventDefault();
                const row = e.target.closest('tr');
                if (row && !row.nextElementSibling) this.addLine();
            }
        });
    },

    get isGst() { return this.taxMode === 'gst'; },
    get isInterstate() {
        if (!this.isGst) return false;
        if (this.isExport) return true;
        return !!(this.sellerState && this.placeOfSupply && this.sellerState !== this.placeOfSupply);
    },
    get zeroRated() { return !this.isGst || (this.isExport && !this.withPaymentOfTax); },

    blankLine() {
        return { item_id: null, name: '', description: '', hsn_sac: '', uqc: 'NOS',
                 quantity: 1, rate: 0, discount_percent: 0, gst_rate: 18, cess_rate: 0 };
    },
    addLine() { this.lines.push(this.blankLine()); },
    removeLine(i) {
        this.lines.splice(i, 1);
        if (!this.lines.length) this.addLine();
    },

    /** Autofill a row from the product catalog when the name matches. */
    pickItem(i, name) {
        const item = this.catalog.find((c) => c.name === name);
        if (!item) { this.lines[i].item_id = null; return; }
        const line = this.lines[i];
        line.item_id = item.id;
        line.hsn_sac = item.hsn_sac ?? '';
        line.uqc = item.uqc ?? 'NOS';
        line.gst_rate = Number(item.gst_rate ?? 18);
        line.cess_rate = Number(item.cess_rate ?? 0);
        if (!Number(line.rate)) {
            line.rate = Number(this.isPurchase ? item.purchase_price : item.sale_price) || 0;
        }
    },

    partyLabel(id) {
        const p = this.parties[String(id)];
        if (!p) return '';
        return p.name + (p.gstin ? ` — ${p.gstin}` : '');
    },

    get filteredParties() {
        const q = this.partyQuery.trim().toLowerCase();
        return Object.entries(this.parties)
            .map(([id, p]) => ({ id, label: p.name + (p.gstin ? ` — ${p.gstin}` : '') }))
            .filter((p) => !q || p.label.toLowerCase().includes(q));
    },

    selectParty(p) {
        this.partyId = p.id;
        this.partyQuery = p.label;
        this.partyOpen = false;
        this.onPartyChange();
    },

    onPartyChange() {
        const p = this.parties[String(this.partyId)];
        if (!p) return;
        if (p.state) this.placeOfSupply = p.state;
        if (p.credit_days && this.issueDate) {
            const d = new Date(this.issueDate);
            d.setDate(d.getDate() + Number(p.credit_days));
            this.dueDate = d.toISOString().slice(0, 10);
        }
    },

    lineTaxable(line) {
        const gross = (Number(line.quantity) || 0) * (Number(line.rate) || 0);
        const disc = gross * ((Number(line.discount_percent) || 0) / 100);
        return round2(gross - Math.min(disc, gross));
    },
    lineGst(line) {
        if (!this.isGst || this.zeroRated) return 0;
        return round2(this.lineTaxable(line) * ((Number(line.gst_rate) || 0) / 100));
    },
    lineCess(line) {
        if (!this.isGst || this.zeroRated) return 0;
        return round2(this.lineTaxable(line) * ((Number(line.cess_rate) || 0) / 100));
    },
    lineTotal(line) { return round2(this.lineTaxable(line) + this.lineGst(line) + this.lineCess(line)); },

    get subtotal() {
        return round2(this.lines.reduce((s, l) => s + (Number(l.quantity) || 0) * (Number(l.rate) || 0), 0));
    },
    get discountTotal() { return round2(this.subtotal - this.taxable); },
    get taxable() { return round2(this.lines.reduce((s, l) => s + this.lineTaxable(l), 0)); },
    get totalGst() { return round2(this.lines.reduce((s, l) => s + this.lineGst(l), 0)); },
    get cess() { return round2(this.lines.reduce((s, l) => s + this.lineCess(l), 0)); },
    get cgst() { return this.isInterstate ? 0 : round2(this.totalGst / 2); },
    get sgst() { return this.isInterstate ? 0 : round2(this.totalGst - this.cgst); },
    get igst() { return this.isInterstate ? this.totalGst : 0; },

    get beforeRound() { return this.taxable + this.totalGst + this.cess + (Number(this.shipping) || 0); },
    get rounding() { return this.roundOff ? round2(Math.round(this.beforeRound) - this.beforeRound) : 0; },
    get grandTotal() { return round2(this.beforeRound + this.rounding); },

    money(v) { return inr.format(Number(v) || 0); },
    inWords(v) { return amountInWords(Number(v) || 0); },
}));

/**
 * Blog post editor: contenteditable rich text + live SEO counters.
 * The visible editor stays in sync with a hidden <textarea name="body">,
 * which is what actually gets submitted.
 */
Alpine.data('postEditor', (config = {}) => ({
    title: config.title ?? '',
    slug: config.slug ?? '',
    metaTitle: config.metaTitle ?? '',
    metaDescription: config.metaDescription ?? '',
    faqs: config.faqs?.length ? config.faqs : [{ question: '', answer: '' }],
    isEdit: config.isEdit ?? false,
    wordCount: 0,

    init() {
        this.syncBody();
    },

    slugify(text) {
        return (text || '')
            .toLowerCase().trim()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .slice(0, 80);
    },

    // Only auto-fill the slug for new posts — changing a live URL breaks links.
    syncSlug() {
        if (!this.isEdit) this.slug = this.slugify(this.title);
    },

    get titleLen() { return (this.metaTitle || this.title || '').length; },
    get descLen() { return (this.metaDescription || '').length; },
    get readingTime() { return Math.max(1, Math.ceil(this.wordCount / 200)); },

    exec(cmd, value = null) {
        this.$refs.editor.focus();
        document.execCommand(cmd, false, value);
        this.syncBody();
    },

    format(tag) { this.exec('formatBlock', tag.toUpperCase()); },

    addLink() {
        const url = prompt('Link URL (include https://)');
        if (url) this.exec('createLink', url);
    },

    syncBody() {
        const html = this.$refs.editor.innerHTML;
        this.$refs.bodyField.value = html;
        this.wordCount = (this.$refs.editor.innerText.trim().match(/\S+/g) || []).length;
    },

    async uploadImage(event) {
        const file = event.target.files[0];
        if (!file) return;

        const data = new FormData();
        data.append('file', file);
        data.append('_token', document.querySelector('meta[name=csrf-token]').content);

        try {
            const res = await fetch('/admin/posts/upload', { method: 'POST', body: data });
            const json = await res.json();
            if (json.url) {
                this.exec('insertHTML',
                    `<img src="${json.url}" alt="" loading="lazy" class="rounded-lg">`);
            }
        } catch (e) {
            alert('Image upload failed.');
        }
        event.target.value = '';
    },

    addFaq() { this.faqs.push({ question: '', answer: '' }); },
    removeFaq(i) {
        this.faqs.splice(i, 1);
        if (!this.faqs.length) this.addFaq();
    },
}));

window.Alpine = Alpine;
Alpine.start();
