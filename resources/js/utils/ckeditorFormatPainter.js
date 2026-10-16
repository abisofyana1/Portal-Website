import { Plugin, ButtonView } from 'ckeditor5';
import { IconPaintRoller } from '@ckeditor/ckeditor5-icons';

/**
 * Custom FormatPainter plugin for CKEditor 5.
 * Copies text formatting (bold, italic, font, color, etc.) and block formatting (heading, alignment)
 * from the current selection and applies it to newly selected text.
 */
export default class CustomFormatPainter extends Plugin {
    static get pluginName() {
        return 'CustomFormatPainter';
    }

    init() {
        const editor = this.editor;
        let copiedAttributes = null;
        let isPainting = false;

        // Register button in UI
        editor.ui.componentFactory.add('formatPainter', (locale) => {
            const view = new ButtonView(locale);

            view.set({
                label: 'Format Painter',
                icon: IconPaintRoller,
                tooltip: true,
                isToggleable: true,
                isOn: false
            });

            const updateButtonState = () => {
                view.isOn = isPainting;
            };

            const cancelPainting = () => {
                if (isPainting) {
                    isPainting = false;
                    copiedAttributes = null;
                    updateButtonState();
                    if (editor.editing.view.getDomRoot()) {
                        editor.editing.view.getDomRoot().classList.remove('format-painter-active');
                    }
                }
            };

            const getSelectionFormatting = () => {
                const model = editor.model;
                const selection = model.document.selection;
                const schema = model.schema;
                const attrs = {
                    textAttributes: {},
                    blockAttributes: {}
                };

                // 1. Text attributes from selection or first character in selection
                for (const [key, value] of selection.getAttributes()) {
                    const props = schema.getAttributeProperties(key);
                    if (props && props.isFormatting) {
                        attrs.textAttributes[key] = value;
                    }
                }

                // If collapsed or attributes were empty, inspect character at cursor or first selected item
                if (Object.keys(attrs.textAttributes).length === 0) {
                    for (const range of selection.getRanges()) {
                        for (const item of range.getItems()) {
                            for (const [key, value] of item.getAttributes()) {
                                const props = schema.getAttributeProperties(key);
                                if (props && props.isFormatting) {
                                    attrs.textAttributes[key] = value;
                                }
                            }
                            if (Object.keys(attrs.textAttributes).length > 0) break;
                        }
                        if (Object.keys(attrs.textAttributes).length > 0) break;
                    }
                }

                // 2. Block attributes from selected blocks (e.g. alignment, heading)
                const blocks = Array.from(selection.getSelectedBlocks());
                if (blocks.length > 0) {
                    const firstBlock = blocks[0];
                    if (firstBlock.hasAttribute('alignment')) {
                        attrs.blockAttributes['alignment'] = firstBlock.getAttribute('alignment');
                    }
                }

                return attrs;
            };

            const applyFormattingToSelection = (targetAttrs) => {
                const model = editor.model;
                const selection = model.document.selection;
                const schema = model.schema;

                if (selection.isCollapsed) return;

                model.change((writer) => {
                    const ranges = Array.from(selection.getRanges());

                    // First: remove existing formatting in range that isn't in targetAttrs
                    for (const range of ranges) {
                        for (const item of Array.from(range.getItems())) {
                            if (item.is('$text') || item.is('$textProxy')) {
                                for (const [attrName] of item.getAttributes()) {
                                    const props = schema.getAttributeProperties(attrName);
                                    if (props && props.isFormatting && !(attrName in targetAttrs.textAttributes)) {
                                        writer.removeAttribute(attrName, writer.createRangeOn(item));
                                    }
                                }
                            }
                        }
                    }

                    // Second: apply new text attributes
                    for (const [attrName, attrVal] of Object.entries(targetAttrs.textAttributes)) {
                        for (const range of ranges) {
                            writer.setAttribute(attrName, attrVal, range);
                        }
                    }

                    // Third: apply block attributes (e.g. alignment)
                    const selectedBlocks = Array.from(selection.getSelectedBlocks());
                    for (const block of selectedBlocks) {
                        if (targetAttrs.blockAttributes.alignment) {
                            writer.setAttribute('alignment', targetAttrs.blockAttributes.alignment, block);
                        } else {
                            writer.removeAttribute('alignment', block);
                        }
                    }
                });
            };

            view.on('execute', () => {
                if (isPainting) {
                    // Turn off painter
                    cancelPainting();
                } else {
                    // Copy formatting from current selection
                    copiedAttributes = getSelectionFormatting();
                    isPainting = true;
                    updateButtonState();

                    const domRoot = editor.editing.view.getDomRoot();
                    if (domRoot) {
                        domRoot.classList.add('format-painter-active');
                    }
                }
            });

            // When user releases mouse in editing view, apply formatting if painting is active
            this.listenTo(editor.editing.view.document, 'mouseup', () => {
                if (!isPainting || !copiedAttributes) return;

                const selection = editor.model.document.selection;
                if (!selection.isCollapsed) {
                    applyFormattingToSelection(copiedAttributes);
                    // Single paint mode: cancel once applied
                    cancelPainting();
                }
            });

            // If user presses Escape key, cancel painting
            this.listenTo(editor.editing.view.document, 'keydown', (evt, data) => {
                if (data.keyCode === 27 && isPainting) { // Escape
                    cancelPainting();
                    evt.stop();
                }
            });

            return view;
        });
    }
}
