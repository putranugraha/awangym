// Parse a deliberately small formatting subset. HTML and links stay plain text.
function inline(text) {
    const spans = [];
    const bold = /\*\*([^*\n]+)\*\*/g;
    let offset = 0;
    for (const match of text.matchAll(bold)) {
        if (match.index > offset) spans.push({ text: text.slice(offset, match.index), bold: false });
        spans.push({ text: match[1], bold: true });
        offset = match.index + match[0].length;
    }
    if (offset < text.length) spans.push({ text: text.slice(offset), bold: false });
    return spans;
}

export function parseChatMessage(text) {
    const blocks = [];
    let paragraph = [];
    let activeList = null;
    const flush = () => {
        if (paragraph.length) blocks.push({ type: 'paragraph', spans: inline(paragraph.join(' ')) });
        paragraph = [];
    };
    for (const raw of String(text).split(/\r?\n/)) {
        const line = raw.trim();
        if (!line) {
            flush();
            activeList = null;
            continue;
        }
        const heading = line.match(/^#{1,3}\s+(.+)$/) || line.match(/^\*\*([^*]+)\*\*$/);
        if (heading) {
            flush();
            activeList = null;
            blocks.push({ type: 'heading', spans: inline(heading[1]) });
            continue;
        }
        const item = line.match(/^(?:([-*•])|(\d+)[.)])\s+(.+)$/);
        if (item) {
            flush();
            const ordered = Boolean(item[2]);
            if (!activeList || activeList.ordered !== ordered) {
                activeList = { type: 'list', ordered, start: ordered ? Number(item[2]) : 1, items: [] };
                blocks.push(activeList);
            }
            activeList.items.push(inline(item[3]));
        } else {
            activeList = null;
            paragraph.push(line);
        }
    }
    flush();
    return blocks;
}

export function renderChatMessage(container, text) {
    const doc = container.ownerDocument;
    const appendInline = (element, spans) => {
        for (const span of spans) {
            if (span.bold) {
                const strong = doc.createElement('strong');
                strong.textContent = span.text;
                element.append(strong);
            } else {
                element.append(doc.createTextNode(span.text));
            }
        }
    };
    for (const block of parseChatMessage(text)) {
        if (block.type === 'list') {
            const list = doc.createElement(block.ordered ? 'ol' : 'ul');
            if (block.ordered) list.start = block.start;
            for (const spans of block.items) {
                const item = doc.createElement('li');
                appendInline(item, spans);
                list.append(item);
            }
            container.append(list);
        } else {
            const element = doc.createElement(block.type === 'heading' ? 'h3' : 'p');
            appendInline(element, block.spans);
            container.append(element);
        }
    }
}
