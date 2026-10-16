import { Plugin, ButtonView, createDropdown, SplitButtonView, addToolbarToDropdown } from 'ckeditor5';
import { IconMultiLevelList } from '@ckeditor/ckeditor5-icons';

/**
 * Multilevel List Style Definitions
 */
export const MULTILEVEL_STYLES = [
    {
        id: 'legal',
        name: '1.  1.1.  1.1.1. (Outline / Legal)',
        className: 'list-multilevel-legal',
        description: '1. Pendahuluan\n   1.1 Latar Belakang\n      1.1.1 Tujuan',
        type: 'ol'
    },
    {
        id: 'standard',
        name: '1.  a.  i. (Word Standard)',
        className: 'list-multilevel-standard',
        description: '1. Item Utama\n   a. Sub Item\n      i. Sub Sub Item',
        type: 'ol'
    },
    {
        id: 'roman',
        name: 'I.  A.  1.  a. (Classic / Roman)',
        className: 'list-multilevel-roman',
        description: 'I. Bab\n   A. Bagian\n      1. Pasal',
        type: 'ol'
    },
    {
        id: 'bullets',
        name: '•  ○  ▪  ▫ (Bullet Hierarchy)',
        className: 'list-multilevel-bullets',
        description: '• Level 1\n   ○ Level 2\n      ▪ Level 3\n         ▫ Level 4',
        type: 'ul'
    }
];

/**
 * WordMultilevelList Plugin for CKEditor 5
 * Provides Microsoft Word-like Multilevel List support:
 * 1. Dropdown UI with style selection (Legal 1.1, Standard 1.a.i, Roman I.A.1, Bullet hierarchy).
 * 2. Model attribute `listMultilevelStyle` mapped to CSS class on list items.
 * 3. Tab and Shift+Tab keyboard indentation.
 */
export default class WordMultilevelList extends Plugin {
    static get pluginName() {
        return 'WordMultilevelList';
    }

    init() {
        const editor = this.editor;
        const model = editor.model;

        // 1. Extend schema to allow listMultilevelStyle on $block elements (list item blocks)
        model.schema.extend('$block', {
            allowAttributes: ['listMultilevelStyle']
        });

        // 2. Conversion for listMultilevelStyle attribute
        editor.conversion.for('downcast').attributeToAttribute({
            model: 'listMultilevelStyle',
            view: (modelAttributeValue) => {
                if (!modelAttributeValue) return null;
                const styleDef = MULTILEVEL_STYLES.find(s => s.id === modelAttributeValue);
                const className = styleDef ? styleDef.className : `list-multilevel-${modelAttributeValue}`;
                return {
                    key: 'class',
                    value: className
                };
            }
        });

        editor.conversion.for('upcast').attributeToAttribute({
            view: {
                classes: /list-multilevel-([a-z0-9_-]+)/
            },
            model: {
                key: 'listMultilevelStyle',
                value: (viewElement) => {
                    for (const cls of viewElement.getClassNames()) {
                        const m = cls.match(/^list-multilevel-([a-z0-9_-]+)$/);
                        if (m) {
                            return m[1];
                        }
                    }
                    return null;
                }
            }
        });

        // 3. Register UI Component: 'multiLevelList' dropdown
        editor.ui.componentFactory.add('multiLevelList', (locale) => {
            const dropdownView = createDropdown(locale, SplitButtonView);
            const mainButtonView = dropdownView.buttonView;

            mainButtonView.set({
                label: 'Multilevel List (Daftar Bertingkat)',
                icon: IconMultiLevelList,
                tooltip: true,
                isToggleable: true
            });

            // Bind isOn state to whether any active block is a list
            const refreshState = () => {
                const selection = model.document.selection;
                const blocks = Array.from(selection.getSelectedBlocks());
                const isList = blocks.some(b => b.hasAttribute('listType'));
                mainButtonView.isOn = isList;
                dropdownView.isEnabled = true;
            };

            model.document.selection.on('change:range', refreshState);
            model.document.on('change:data', refreshState);

            // Clicking the main button toggles default Legal (1, 1.1, 1.1.1) list
            mainButtonView.on('execute', () => {
                const selection = model.document.selection;
                const blocks = Array.from(selection.getSelectedBlocks());
                const isList = blocks.some(b => b.hasAttribute('listType'));

                if (isList) {
                    const currentStyle = blocks[0]?.getAttribute('listMultilevelStyle');
                    if (currentStyle === 'legal') {
                        // If already legal, toggle off list
                        editor.execute('numberedList');
                    } else {
                        this._applyMultilevelStyle('legal');
                    }
                } else {
                    this._applyMultilevelStyle('legal');
                }
                editor.editing.view.focus();
            });

            // Add toolbar items inside dropdown
            const buttons = MULTILEVEL_STYLES.map(style => {
                const btn = new ButtonView(locale);
                btn.set({
                    label: `${style.name}\n${style.description}`,
                    withText: true,
                    isToggleable: true
                });

                btn.on('execute', () => {
                    this._applyMultilevelStyle(style.id);
                    dropdownView.isOpen = false;
                    editor.editing.view.focus();
                });

                return btn;
            });

            addToolbarToDropdown(dropdownView, buttons, {
                isVertical: true,
                ariaLabel: 'Multilevel list styles'
            });

            return dropdownView;
        });

        // 4. Register UI Component: 'alignColon' (Perataan Titik Dua Otomatis)
        editor.ui.componentFactory.add('alignColon', (locale) => {
            const buttonView = new ButtonView(locale);
            buttonView.set({
                label: 'Ratakan Titik Dua (Colon Alignment)',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M5 4h3v2H5V4zm0 5h3v2H5V9zm0 5h3v2H5v-2zm5-10h1v2h-1V4zm0 5h1v2h-1V9zm0 5h1v2h-1v-2zm3-10h2v2h-2V4zm0 5h2v2h-2V9zm0 5h2v2h-2v-2z" fill="currentColor"/></svg>',
                tooltip: 'Ratakan Titik Dua ( : ) Lurus Sempurna Antar Baris',
                withText: false
            });

            buttonView.on('execute', () => {
                this._alignColons();
                editor.editing.view.focus();
            });

            return buttonView;
        });

        // 5. Register UI Component: 'toggleTableBorder' (Tombol Hilangkan/Munculkan Border Tabel)
        editor.ui.componentFactory.add('toggleTableBorder', (locale) => {
            const buttonView = new ButtonView(locale);
            buttonView.set({
                label: 'Hilangkan / Munculkan Border Tabel',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M3 3h14v14H3V3zm2 2v10h10V5H5zm2 2h6v6H7V7z" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M4 16L16 4" stroke="currentColor" stroke-width="1.5"/></svg>',
                tooltip: 'Hilangkan / Munculkan Border Tabel & Sel (Toggle Border)',
                withText: false,
                isToggleable: true
            });

            // Bind isEnabled to whether selection is inside a table
            const checkTableState = () => {
                const selection = model.document.selection;
                const selectedElement = selection.getSelectedElement();
                const blocks = Array.from(selection.getSelectedBlocks());
                const table = (selectedElement && selectedElement.is('element', 'table')) 
                              ? selectedElement 
                              : (blocks.length > 0 ? blocks[0].findAncestor('table') : null);

                buttonView.isEnabled = !!table;
                if (table) {
                    const currentStyle = table.getAttribute('borderStyle');
                    buttonView.isOn = currentStyle === 'none';
                } else {
                    buttonView.isOn = false;
                }
            };

            model.document.selection.on('change:range', checkTableState);

            buttonView.on('execute', () => {
                this._toggleTableBorder();
                editor.editing.view.focus();
            });

            return buttonView;
        });

        // 6. Tab and Shift+Tab key bindings for increasing/decreasing list indent
        this._setupKeyboardNavigation();
    }

    /**
     * Toggles table border on/off
     */
    _toggleTableBorder() {
        const editor = this.editor;
        const model = editor.model;
        const selection = model.document.selection;
        const selectedElement = selection.getSelectedElement();
        const blocks = Array.from(selection.getSelectedBlocks());

        // 1. Check if selection is an entire table element
        const isTableSelected = selectedElement && selectedElement.is('element', 'table');

        // 2. Find selected cell(s)
        const selectedCells = [];
        for (const block of blocks) {
            const cell = block.findAncestor('tableCell');
            if (cell && !selectedCells.includes(cell)) {
                selectedCells.push(cell);
            }
        }
        if (selectedCells.length === 0) {
            const firstPos = selection.getFirstPosition();
            if (firstPos) {
                const cell = firstPos.findAncestor('tableCell');
                if (cell) selectedCells.push(cell);
            }
        }

        // 3. Find table
        let table = isTableSelected ? selectedElement : (selectedCells.length > 0 ? selectedCells[0].findAncestor('table') : null);
        if (!table && blocks.length > 0) {
            table = blocks[0].findAncestor('table');
        }
        if (!table) return;

        // Count total cells in table to know if ALL cells are selected
        let totalCellsInTable = 0;
        for (const row of table.getChildren()) {
            if (row.is('element', 'tableRow')) {
                for (const cell of row.getChildren()) {
                    if (cell.is('element', 'tableCell')) {
                        totalCellsInTable++;
                    }
                }
            }
        }

        const isAllSelected = isTableSelected || (selectedCells.length >= totalCellsInTable && totalCellsInTable > 0);

        if (isAllSelected) {
            // Whole table border toggle
            const currentStyle = table.getAttribute('tableBorderStyle') || table.getAttribute('borderStyle');
            const hasNoBorder = currentStyle === 'none' || table.getAttribute('borderWidth') === '0px' || table.getAttribute('borderWidth') === '0';

            if (!hasNoBorder) {
                try {
                    editor.execute('tableBorderStyle', { value: 'none' });
                    editor.execute('tableBorderWidth', { value: '0px' });
                    editor.execute('tableBorderColor', { value: 'transparent' });
                } catch (e) {}

                model.change(writer => {
                    writer.setAttribute('tableBorderStyle', 'none', table);
                    writer.setAttribute('borderStyle', 'none', table);
                    writer.setAttribute('borderWidth', '0px', table);
                    writer.setAttribute('borderColor', 'transparent', table);

                    for (const row of table.getChildren()) {
                        if (row.is('element', 'tableRow')) {
                            for (const cell of row.getChildren()) {
                                if (cell.is('element', 'tableCell')) {
                                    writer.setAttribute('tableCellBorderStyle', 'none', cell);
                                    writer.setAttribute('borderStyle', 'none', cell);
                                    writer.setAttribute('borderWidth', '0px', cell);
                                    writer.setAttribute('borderColor', 'transparent', cell);
                                }
                            }
                        }
                    }
                });
            } else {
                try {
                    editor.execute('tableBorderStyle', { value: 'solid' });
                    editor.execute('tableBorderWidth', { value: '1px' });
                    editor.execute('tableBorderColor', { value: '#cbd5e1' });
                } catch (e) {}

                model.change(writer => {
                    writer.setAttribute('tableBorderStyle', 'solid', table);
                    writer.setAttribute('borderStyle', 'solid', table);
                    writer.setAttribute('borderWidth', '1px', table);
                    writer.setAttribute('borderColor', '#cbd5e1', table);

                    for (const row of table.getChildren()) {
                        if (row.is('element', 'tableRow')) {
                            for (const cell of row.getChildren()) {
                                if (cell.is('element', 'tableCell')) {
                                    writer.setAttribute('tableCellBorderStyle', 'solid', cell);
                                    writer.setAttribute('borderStyle', 'solid', cell);
                                    writer.setAttribute('borderWidth', '1px', cell);
                                    writer.setAttribute('borderColor', '#cbd5e1', cell);
                                }
                            }
                        }
                    }
                });
            }
        } else {
            // Target ONLY the active / selected cell(s) (e.g. column 1 row 3)
            const targetCell = selectedCells[0];
            const currentStyle = targetCell.getAttribute('tableCellBorderStyle') || targetCell.getAttribute('borderStyle');
            const hasNoBorder = currentStyle === 'none' || targetCell.getAttribute('borderWidth') === '0px' || targetCell.getAttribute('borderWidth') === '0';

            model.change(writer => {
                for (const cell of selectedCells) {
                    if (!hasNoBorder) {
                        // Turn off border for selected cell(s)
                        writer.setAttribute('tableCellBorderStyle', 'none', cell);
                        writer.setAttribute('borderStyle', 'none', cell);
                        writer.setAttribute('borderWidth', '0px', cell);
                        writer.setAttribute('borderColor', 'transparent', cell);
                    } else {
                        // Turn on border for selected cell(s)
                        writer.setAttribute('tableCellBorderStyle', 'solid', cell);
                        writer.setAttribute('borderStyle', 'solid', cell);
                        writer.setAttribute('borderWidth', '1px', cell);
                        writer.setAttribute('borderColor', '#cbd5e1', cell);
                    }
                }
            });
        }
    }

    /**
     * Formats lines containing colon ':' into perfectly aligned invisible layout
     */
    _alignColons() {
        const editor = this.editor;
        const model = editor.model;
        const selection = model.document.selection;
        const blocks = Array.from(selection.getSelectedBlocks());

        if (blocks.length === 0) return;

        // Check if any block contains ':'
        const hasColon = blocks.some(b => {
            const text = Array.from(b.getChildren()).map(c => c.data || '').join('');
            return text.includes(':');
        });

        if (!hasColon) {
            // If no colon, insert a 3-row clean borderless form table template (Label | : | Nilai)
            editor.execute('insertTable', { rows: 3, columns: 3 });
            editor.model.change(writer => {
                const sel = editor.model.document.selection;
                const newTable = sel.getSelectedElement() || sel.getFirstPosition().findAncestor('table');
                if (newTable) {
                    writer.setAttribute('tableIndent', 0, newTable);
                }
            });
            return;
        }

        // Convert the selected lines containing colons into an elegant borderless 3-column table
        const rowsData = [];
        blocks.forEach(block => {
            let fullText = '';
            for (const child of block.getChildren()) {
                if (child.is('$text') || child.is('text')) {
                    fullText += child.data;
                }
            }

            if (fullText.includes(':')) {
                const firstColon = fullText.indexOf(':');
                const label = fullText.substring(0, firstColon).trim();
                const value = fullText.substring(firstColon + 1).trim();
                rowsData.push({ label, value });
            } else if (fullText.trim()) {
                rowsData.push({ label: fullText.trim(), value: '' });
            }
        });

        if (rowsData.length > 0) {
            let tableHtml = '<table class="cxc-colon-table" style="border:none;border-collapse:collapse;width:100%;margin-top:0.5em;margin-bottom:0.5em;"><tbody>';
            rowsData.forEach(row => {
                tableHtml += `<tr style="border:none;">` +
                    `<td style="border:none;padding:2px 8px 2px 0;vertical-align:top;font-weight:inherit;">${row.label}</td>` +
                    `<td style="border:none;padding:2px 8px 2px 0;width:0.40cm;min-width:0.40cm;text-align:center;vertical-align:top;font-weight:bold;">:</td>` +
                    `<td style="border:none;padding:2px 0;vertical-align:top;">${row.value || '&nbsp;'}</td>` +
                    `</tr>`;
            });
            tableHtml += '</tbody></table><p>&nbsp;</p>';

            const viewFragment = editor.data.processor.toView(tableHtml);
            const modelFragment = editor.data.toModel(viewFragment);

            model.change(writer => {
                // Delete selected blocks and replace with the aligned table
                const firstBlock = blocks[0];
                const lastBlock = blocks[blocks.length - 1];
                const range = writer.createRange(writer.createPositionBefore(firstBlock), writer.createPositionAfter(lastBlock));
                writer.remove(range);
                model.insertContent(modelFragment, range.start);
            });
        }
    }

    /**
     * Applies a multilevel list style to the selected blocks
     */
    _applyMultilevelStyle(styleId) {
        const editor = this.editor;
        const model = editor.model;
        const styleDef = MULTILEVEL_STYLES.find(s => s.id === styleId) || MULTILEVEL_STYLES[0];

        model.change((writer) => {
            const selection = model.document.selection;
            const blocks = Array.from(selection.getSelectedBlocks());

            // If selection is not yet a list, activate numberedList or bulletedList first
            const unlisted = blocks.filter(b => !b.hasAttribute('listType'));
            if (unlisted.length > 0) {
                const commandToRun = styleDef.type === 'ul' ? 'bulletedList' : 'numberedList';
                editor.execute(commandToRun);
            }

            // Apply multilevel style attribute to all selected list blocks
            const refreshedBlocks = Array.from(selection.getSelectedBlocks());
            refreshedBlocks.forEach(block => {
                if (block.hasAttribute('listType')) {
                    writer.setAttribute('listMultilevelStyle', styleDef.id, block);
                }
            });
        });
    }

    /**
     * Ensures Tab and Shift+Tab indent/outdent list blocks seamlessly
     */
    _setupKeyboardNavigation() {
        const editor = this.editor;
        const viewDocument = editor.editing.view.document;

        viewDocument.on('keydown', (evt, data) => {
            const isTab = data.keyCode === 9 || data.domEvent?.key === 'Tab';
            const isBackspace = data.keyCode === 8 || data.domEvent?.key === 'Backspace';

            if (isTab) {
                const selection = editor.model.document.selection;
                const blocks = Array.from(selection.getSelectedBlocks());
                const isList = blocks.some(b => b.hasAttribute('listType'));

                if (isList) {
                    // Check if cursor is at the very beginning of the list item
                    const firstBlock = blocks[0];
                    const isAtBlockStart = selection.isCollapsed && selection.getFirstPosition().isAtStart;

                    if (isAtBlockStart || !selection.isCollapsed) {
                        // Word-like list indent/outdent
                        const commandName = data.shiftKey ? 'outdentList' : 'indentList';
                        const cmd = editor.commands.get(commandName);
                        if (cmd && cmd.isEnabled) {
                            editor.execute(commandName);
                            data.preventDefault();
                            evt.stop();
                            return;
                        }
                    } else {
                        // Cursor is in the middle of list item text: Insert tab spaces at cursor!
                        editor.model.change(writer => {
                            editor.model.insertContent(writer.createText('\u00A0\u00A0\u00A0\u00A0'), selection);
                        });
                        data.preventDefault();
                        evt.stop();
                        return;
                    }
                } else {
                    // If inside a table, allow native table cell navigation
                    const selectedElement = selection.getSelectedElement();
                    const table = (selectedElement && selectedElement.is('element', 'table')) 
                                  ? selectedElement 
                                  : (blocks.length > 0 ? blocks[0].findAncestor('table') : null);

                    if (!table) {
                        const isAtBlockStart = selection.isCollapsed && selection.getFirstPosition().isAtStart;
                        if (isAtBlockStart && data.shiftKey) {
                            // Outdent block on Shift+Tab at start of line
                            const outdentBlockCmd = editor.commands.get('outdentBlock');
                            if (outdentBlockCmd && outdentBlockCmd.isEnabled) {
                                editor.execute('outdentBlock');
                                data.preventDefault();
                                evt.stop();
                                return;
                            }
                        }

                        // Insert 4 non-breaking spaces at cursor position (moves only text from cursor onwards)
                        editor.model.change(writer => {
                            editor.model.insertContent(writer.createText('\u00A0\u00A0\u00A0\u00A0'), selection);
                        });
                        data.preventDefault();
                        evt.stop();
                    }
                }
            } else if (isBackspace) {
                // Check if the preceding characters are 4 non-breaking spaces (\u00A0\u00A0\u00A0\u00A0)
                const selection = editor.model.document.selection;
                if (selection.isCollapsed) {
                    const pos = selection.getFirstPosition();
                    const parent = pos.parent;
                    const offset = pos.offset;

                    // Inspect preceding text inside parent
                    let precedingText = '';
                    for (const node of parent.getChildren()) {
                        if (node.is('$text') || node.is('text')) {
                            precedingText += node.data;
                        }
                    }

                    // Check if 4 spaces or \u00A0 precede the current position
                    const textBefore = precedingText.substring(0, offset);
                    if (textBefore.endsWith('\u00A0\u00A0\u00A0\u00A0') || textBefore.endsWith('    ')) {
                        const startPos = pos.getShiftedBy(-4);
                        const rangeToDelete = editor.model.createRange(startPos, pos);
                        editor.model.change(writer => {
                            writer.remove(rangeToDelete);
                        });
                        data.preventDefault();
                        evt.stop();
                    }
                }
            }
        }, { priority: 'high' });
    }
}
