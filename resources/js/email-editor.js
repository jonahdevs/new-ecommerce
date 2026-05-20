import grapesjs from 'grapesjs'
import newsletter from 'grapesjs-preset-newsletter'
import 'grapesjs/dist/css/grapes.min.css'

const emailEditor = (options = {}) => ({
    editor: null,

    init() {
        const el = this.$refs.emailEditor

        this.editor = grapesjs.init({
            container: el,
            fromElement: false,
            height: '720px',
            width: 'auto',
            storageManager: false,
            plugins: [newsletter],
            pluginsOpts: {
                'gjs-preset-newsletter': {
                    modalLabelImport: 'Paste HTML here',
                    modalLabelExport: 'Copy the code and use it wherever you want',
                    codeViewerTheme: 'material',
                    inlineCss: true,
                    cellStyle: {
                        'font-size': '14px',
                        'font-weight': 300,
                        'vertical-align': 'top',
                        color: 'rgb(111, 119, 125)',
                        margin: 0,
                        padding: 0,
                    },
                },
            },
            canvas: {
                styles: [],
            },
        })

        // Register variable tokens as draggable blocks under a "Variables" category
        if (Array.isArray(options.variables) && options.variables.length) {
            const bm = this.editor.BlockManager
            options.variables.forEach((v) => {
                bm.add(`var-${v.token}`, {
                    label: v.token,
                    category: 'Variables',
                    content: `<span>${v.token}</span>`,
                    attributes: { title: v.description ?? '' },
                })
            })
        }

        // Load existing HTML content if provided
        if (options.html) {
            this.editor.setComponents(options.html)
        }

        // Sync HTML changes to hidden input / Livewire
        this.editor.on('update', () => {
            this._sync()
        })

        // Also sync on component/style changes
        this.editor.on('component:update', () => this._sync())
        this.editor.on('style:update', () => this._sync())
    },

    _sync() {
        const html = this.editor.runCommand('gjs-get-inlined-html')

        if (this.$refs.htmlInput) {
            this.$refs.htmlInput.value = html
            this.$refs.htmlInput.dispatchEvent(new Event('input'))
        }

        if (this.$refs.jsonInput) {
            const json = JSON.stringify(this.editor.getProjectData())
            this.$refs.jsonInput.value = json
            this.$refs.jsonInput.dispatchEvent(new Event('input'))
        }
    },

    loadHtml(html) {
        this.editor?.setComponents(html || '')
    },

    loadProject(json) {
        if (json && this.editor) {
            try {
                this.editor.loadProjectData(typeof json === 'string' ? JSON.parse(json) : json)
            } catch {
                // malformed JSON — ignore and keep current state
            }
        }
    },

    getHtml() {
        return this.editor?.runCommand('gjs-get-inlined-html') ?? ''
    },

    destroy() {
        this.editor?.destroy()
        this.editor = null
    },
})

export default emailEditor
