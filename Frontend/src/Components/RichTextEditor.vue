<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Underline from '@tiptap/extension-underline'
import Link from '@tiptap/extension-link'
import { TextStyle } from '@tiptap/extension-text-style'
import { Color } from '@tiptap/extension-color'
import { Table } from '@tiptap/extension-table'
import { TableRow } from '@tiptap/extension-table-row'
import { TableCell } from '@tiptap/extension-table-cell'
import { TableHeader } from '@tiptap/extension-table-header'
import { watch, onBeforeUnmount } from 'vue'

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
})

const emit = defineEmits(['update:modelValue'])

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit.configure({
            link: false,
            underline: false,
        }),
        TextStyle,
        Color,
        Underline,
        Link.configure({
            openOnClick: false,
            HTMLAttributes: {
                class: 'text-blue-600 underline cursor-pointer',
            },
        }),
        Table.configure({
            resizable: true,
        }),
        TableRow,
        TableHeader,
        TableCell,
    ],
    editorProps: {
        attributes: {
            class: 'prose prose-sm sm:prose lg:prose-lg xl:prose-2xl mx-auto focus:outline-none min-h-[300px] p-6 text-gray-800 bg-white rounded-b-2xl max-w-none',
        },
    },
    onUpdate: ({ editor }) => {
        emit('update:modelValue', editor.getHTML())
    },
})

watch(() => props.modelValue, (value) => {
    if (!editor.value) return
    const isSame = editor.value.getHTML() === value
    // Only set content if it's fundamentally different (e.g. initially loaded or changed from outside)
    if (!isSame) {
        editor.value.commands.setContent(value, false)
    }
})

onBeforeUnmount(() => {
    if (editor.value) {
        editor.value.destroy()
    }
})
</script>

<template>
    <div v-if="editor" class="border border-gray-100 rounded-2xl overflow-hidden shadow-sm bg-gray-50 flex flex-col h-[550px]">
        <!-- Toolbar -->
        <div class="flex flex-wrap items-center gap-1 p-2 border-b border-gray-100 bg-white/80 backdrop-blur-md sticky top-0 z-10">
            <button 
                type="button"
                @click="editor.chain().focus().toggleBold().run()" 
                :class="{ 'bg-blue-100 text-blue-600': editor.isActive('bold') }"
                class="p-2 rounded-lg hover:bg-gray-100 transition-all font-bold text-xs flex items-center gap-1"
                title="Gras"
            >
                B
            </button>
            <button 
                type="button"
                @click="editor.chain().focus().toggleItalic().run()" 
                :class="{ 'bg-blue-100 text-blue-600': editor.isActive('italic') }"
                class="p-2 rounded-lg hover:bg-gray-100 transition-all italic text-xs px-3"
                title="Italique"
            >
                I
            </button>
            <button 
                type="button"
                @click="editor.chain().focus().toggleUnderline().run()" 
                :class="{ 'bg-blue-100 text-blue-600': editor.isActive('underline') }"
                class="p-2 rounded-lg hover:bg-gray-100 transition-all underline text-xs px-3"
                title="Souligné"
            >
                U
            </button>
            
            <div class="h-6 w-px bg-gray-200 mx-1"></div>

            <!-- Color Picker -->
            <div class="flex items-center gap-1.5 px-1">
                <div class="relative w-6 h-6 rounded-md overflow-hidden border border-gray-200 shadow-sm flex items-center justify-center bg-white group hover:border-gray-400 transition-all">
                    <input 
                        type="color" 
                        @input="editor.chain().focus().setColor($event.target.value).run()"
                        :value="editor.getAttributes('textStyle').color || '#000000'"
                        class="absolute inset-0 w-10 h-10 -translate-x-2 -translate-y-2 cursor-pointer border-0 p-0"
                        title="Couleur de police"
                    />
                </div>
                <button 
                    v-if="editor.getAttributes('textStyle').color"
                    type="button"
                    @click="editor.chain().focus().unsetColor().run()"
                    class="p-1 text-[10px] font-black uppercase text-red-500 hover:text-red-700 hover:bg-red-50 rounded transition-all tracking-wider"
                    title="Réinitialiser la couleur"
                >
                    Effacer
                </button>
            </div>

            <div class="h-6 w-px bg-gray-200 mx-1"></div>

            <button 
                type="button"
                @click="editor.chain().focus().toggleHeading({ level: 1 }).run()" 
                :class="{ 'bg-blue-100 text-blue-600': editor.isActive('heading', { level: 1 }) }"
                class="p-2 rounded-lg hover:bg-gray-100 transition-all font-black text-xs"
                title="Titre 1"
            >
                H1
            </button>
            <button 
                type="button"
                @click="editor.chain().focus().toggleHeading({ level: 2 }).run()" 
                :class="{ 'bg-blue-100 text-blue-600': editor.isActive('heading', { level: 2 }) }"
                class="p-2 rounded-lg hover:bg-gray-100 transition-all font-black text-xs"
                title="Titre 2"
            >
                H2
            </button>

            <div class="h-6 w-px bg-gray-200 mx-1"></div>

            <button 
                type="button"
                @click="editor.chain().focus().toggleBulletList().run()" 
                :class="{ 'bg-blue-100 text-blue-600': editor.isActive('bulletList') }"
                class="p-2 rounded-lg hover:bg-gray-100 transition-all"
                title="Liste à puces"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <button 
                type="button"
                @click="editor.chain().focus().toggleOrderedList().run()" 
                :class="{ 'bg-blue-100 text-blue-600': editor.isActive('orderedList') }"
                class="p-2 rounded-lg hover:bg-gray-100 transition-all"
                title="Liste ordonnée"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 6h13M7 12h13M7 18h13M3 6h.01M3 12h.01M3 18h.01" />
                </svg>
            </button>

            <div class="h-6 w-px bg-gray-200 mx-1"></div>

            <button 
                type="button"
                @click="editor.chain().focus().toggleBlockquote().run()" 
                :class="{ 'bg-blue-100 text-blue-600': editor.isActive('blockquote') }"
                class="p-2 rounded-lg hover:bg-gray-100 transition-all"
                title="Citation"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                </svg>
            </button>

            <div class="h-6 w-px bg-gray-200 mx-1"></div>

            <button 
                type="button"
                @click="editor.chain().focus().undo().run()" 
                :disabled="!editor.can().undo()"
                class="p-2 rounded-lg hover:bg-gray-100 transition-all disabled:opacity-20"
                title="Annuler"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </button>

            <div class="h-6 w-px bg-gray-200 mx-1"></div>

            <button 
                type="button"
                @click="editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()" 
                class="p-2 rounded-lg hover:bg-gray-100 transition-all text-xs flex items-center gap-1 font-bold text-gray-700"
                title="Insérer un tableau (3x3)"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </button>

            <!-- Table Actions Sub-toolbar -->
            <transition name="fade">
                <div v-if="editor.isActive('table')" class="flex items-center gap-1 bg-blue-50/50 border border-blue-100 rounded-lg p-1 animate-fade-in">
                    <button 
                        type="button"
                        @click="editor.chain().focus().addRowAfter().run()" 
                        class="px-2 py-1 text-[10px] font-black uppercase text-blue-700 hover:bg-blue-100 rounded transition-all"
                        title="Ajouter une ligne en dessous"
                    >
                        + Ligne
                    </button>
                    <button 
                        type="button"
                        @click="editor.chain().focus().deleteRow().run()" 
                        class="px-2 py-1 text-[10px] font-black uppercase text-red-600 hover:bg-red-50 rounded transition-all"
                        title="Supprimer la ligne"
                    >
                        - Ligne
                    </button>
                    <div class="h-4 w-px bg-blue-100 mx-0.5"></div>
                    <button 
                        type="button"
                        @click="editor.chain().focus().addColumnAfter().run()" 
                        class="px-2 py-1 text-[10px] font-black uppercase text-blue-700 hover:bg-blue-100 rounded transition-all"
                        title="Ajouter une colonne à droite"
                    >
                        + Col
                    </button>
                    <button 
                        type="button"
                        @click="editor.chain().focus().deleteColumn().run()" 
                        class="px-2 py-1 text-[10px] font-black uppercase text-red-600 hover:bg-red-50 rounded transition-all"
                        title="Supprimer la colonne"
                    >
                        - Col
                    </button>
                    <div class="h-4 w-px bg-blue-100 mx-0.5"></div>
                    <button 
                        type="button"
                        @click="editor.chain().focus().deleteTable().run()" 
                        class="px-2 py-1 text-[10px] font-black uppercase text-red-700 bg-red-50 hover:bg-red-100 rounded transition-all"
                        title="Supprimer le tableau"
                    >
                        Suppr. Tab
                    </button>
                </div>
            </transition>
        </div>

        <!-- Editor Area -->
        <editor-content :editor="editor" class="bg-white flex-1 overflow-y-auto custom-scrollbar" />
    </div>
</template>

<style>
/* Tiptap styles */
.ProseMirror {
    outline: none !important;
}

/* Table styling inside Tiptap editor */
.ProseMirror table {
    border-collapse: collapse;
    table-layout: fixed;
    width: 100%;
    margin: 2rem 0;
    overflow: hidden;
    border-radius: 1rem;
    border: 1px solid #e5e7eb;
}

.ProseMirror th,
.ProseMirror td {
    min-width: 1em;
    border: 1px solid #e5e7eb;
    padding: 0.75rem 1rem;
    vertical-align: top;
    box-sizing: border-box;
    position: relative;
}

.ProseMirror th {
    font-weight: 800;
    text-align: left;
    background-color: #f9fafb;
    color: #111827;
}

.ProseMirror .selectedCell:after {
    z-index: 2;
    position: absolute;
    content: "";
    left: 0; right: 0; top: 0; bottom: 0;
    background: rgba(200, 200, 255, 0.4);
    pointer-events: none;
}

.ProseMirror .column-resize-handle {
    position: absolute;
    right: -2px;
    top: 0;
    bottom: -2px;
    width: 4px;
    background-color: #3b82f6;
    pointer-events: none;
}

.ProseMirror blockquote {
    border-left: 3px solid #3b82f6;
    padding-left: 1rem;
    font-style: italic;
    color: #4b5563;
}

.ProseMirror ul {
    list-style-type: disc;
    padding-left: 1.5rem;
}

.ProseMirror ol {
    list-style-type: decimal;
    padding-left: 1.5rem;
}

.ProseMirror h1 {
    font-size: 2rem;
    font-weight: 900;
    margin-bottom: 1rem;
}

.ProseMirror h2 {
    font-size: 1.5rem;
    font-weight: 800;
    margin-bottom: 0.75rem;
}
</style>
