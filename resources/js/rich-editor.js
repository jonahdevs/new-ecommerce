import { Editor } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'
import { Placeholder } from '@tiptap/extensions'
import TextAlign from '@tiptap/extension-text-align'

// Note: In Tiptap v3, StarterKit already includes Underline and Link.
// Importing them separately would cause duplicate extension errors.

export default (wireProperty, placeholder = 'Start writing...') => ({
    editor: null,

    init() {
        this.editor = new Editor({
            element: this.$refs.editor,
            extensions: [
                StarterKit,
                Placeholder.configure({ placeholder }),
                TextAlign.configure({ types: ['heading', 'paragraph'] }),
            ],
            content: this.$wire.get(wireProperty) || '',
            editorProps: {
                attributes: {
                    class: 'prose max-w-none min-h-48 p-4 focus:outline-none',
                },
            },
            onUpdate: ({ editor }) => {
                this.$wire.set(wireProperty, editor.getHTML())
            },
        })

        this.$wire.$watch(wireProperty, (value) => {
            if (value !== this.editor.getHTML()) {
                this.editor.commands.setContent(value || '', false)
            }
        })
    },

    destroy() {
        this.editor?.destroy()
    },

    // Toolbar actions
    toggleBold() { this.editor.chain().focus().toggleBold().run() },
    toggleItalic() { this.editor.chain().focus().toggleItalic().run() },
    toggleUnderline() { this.editor.chain().focus().toggleUnderline().run() },
    toggleHeading(level) { this.editor.chain().focus().toggleHeading({ level }).run() },
    toggleBulletList() { this.editor.chain().focus().toggleBulletList().run() },
    toggleOrderedList() { this.editor.chain().focus().toggleOrderedList().run() },
    toggleBlockquote() { this.editor.chain().focus().toggleBlockquote().run() },
    alignLeft() { this.editor.chain().focus().setTextAlign('left').run() },
    alignCenter() { this.editor.chain().focus().setTextAlign('center').run() },
    alignRight() { this.editor.chain().focus().setTextAlign('right').run() },
    undo() { this.editor.chain().focus().undo().run() },
    redo() { this.editor.chain().focus().redo().run() },

    isActive(type, opts = {}) {
        return this.editor?.isActive(type, opts) ?? false
    },
})
