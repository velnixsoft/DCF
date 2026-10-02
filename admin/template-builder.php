<?php
require '../config/db.php';
require '../includes/functions.php';
require '../includes/member_module.php';
require '../includes/template_builder.php';

if (!canAccessModule($pdo, 'coordinator', 'page.member_documents')) {
    setFlash('error', 'Access denied.');
    header('Location: dashboard.php');
    exit;
}

$templateTypes = tb_allowed_template_types();
$typeLabels = [];
foreach ($templateTypes as $type) {
    $typeLabels[$type] = tb_type_label($type);
}

require 'includes/header.php';
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6">
            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4 mb-5">
                <div>
                    <h3 class="text-2xl md:text-3xl font-medium text-gray-700 dark:text-white">Template Builder</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Design reusable layouts for members, volunteers, visitors, and student ambassador certificates.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="document_studio.php" class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-lg text-sm">Document Studio</a>
                    <button type="button" id="saveTemplateBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
                        <i class="fa-solid fa-floppy-disk mr-1"></i> Save Template
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-[280px_minmax(0,1fr)_280px] gap-4">
                <aside class="bg-white dark:bg-gray-800 rounded-xl border dark:border-gray-700 shadow-sm p-4 space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Template Name</label>
                        <input id="templateName" type="text" value="New Template" class="w-full px-3 py-2 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        <input id="templateId" type="hidden" value="">
                        <input id="existingBackground" type="hidden" value="">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Document Type</label>
                        <select id="templateType" class="w-full px-3 py-2 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            <?php 
                            $requestedType = $_GET['type'] ?? '';
                            foreach ($typeLabels as $value => $label): ?>
                                <option value="<?php echo htmlspecialchars($value); ?>" <?php echo ($requestedType === $value) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Width</label>
                            <input id="canvasWidth" type="number" min="200" max="3000" value="856" class="w-full px-3 py-2 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Height</label>
                            <input id="canvasHeight" type="number" min="200" max="3000" value="540" class="w-full px-3 py-2 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-500 mb-2">Elements</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" data-add="text" class="tb-tool"><i class="fa-solid fa-font"></i><span>Text</span></button>
                            <button type="button" data-add="photo" class="tb-tool"><i class="fa-solid fa-user"></i><span>Photo</span></button>
                            <button type="button" data-add="qr" class="tb-tool"><i class="fa-solid fa-qrcode"></i><span>QR</span></button>
                            <button type="button" data-add="logo" class="tb-tool"><i class="fa-solid fa-image"></i><span>Logo</span></button>
                            <button type="button" data-add="signature" class="tb-tool"><i class="fa-solid fa-signature"></i><span>Sign</span></button>
                            <button type="button" data-add="rect" class="tb-tool"><i class="fa-regular fa-square"></i><span>Box</span></button>
                            <button type="button" data-add="circle" class="tb-tool"><i class="fa-regular fa-circle"></i><span>Circle</span></button>
                            <button type="button" data-add="line" class="tb-tool"><i class="fa-solid fa-minus"></i><span>Line</span></button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Background Image</label>
                        <input id="backgroundUpload" type="file" accept="image/*" class="w-full text-xs dark:text-gray-300">
                    </div>
                </aside>

                <section class="min-w-0">
                    <div class="bg-white dark:bg-gray-800 rounded-xl border dark:border-gray-700 shadow-sm p-3">
                        <div class="flex flex-wrap items-center gap-2 mb-3">
                            <button type="button" id="bringForward" class="tb-icon" title="Bring forward"><i class="fa-solid fa-arrow-up"></i></button>
                            <button type="button" id="sendBackward" class="tb-icon" title="Send backward"><i class="fa-solid fa-arrow-down"></i></button>
                            <button type="button" id="duplicateObject" class="tb-icon" title="Duplicate"><i class="fa-regular fa-copy"></i></button>
                            <button type="button" id="deleteObject" class="tb-icon text-red-600" title="Delete"><i class="fa-regular fa-trash-can"></i></button>
                            <span class="h-7 border-l dark:border-gray-700 mx-1"></span>
                            <button type="button" id="clearCanvas" class="text-xs px-3 py-2 rounded-lg border dark:border-gray-600 dark:text-gray-200">Clear</button>
                            <button type="button" id="previewTemplate" class="text-xs px-3 py-2 rounded-lg bg-emerald-600 text-white">Preview</button>
                        </div>
                        <div class="overflow-auto rounded-lg bg-slate-100 dark:bg-gray-900 p-4 max-h-[72vh]">
                            <div id="canvasWrap" class="mx-auto shadow bg-white" style="width:856px;height:540px;">
                                <canvas id="templateCanvas" width="856" height="540"></canvas>
                            </div>
                        </div>
                    </div>
                </section>

                <aside class="bg-white dark:bg-gray-800 rounded-xl border dark:border-gray-700 shadow-sm p-4 space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Saved Templates</label>
                        <select id="templateList" class="w-full px-3 py-2 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            <option value="">Select template</option>
                        </select>
                        <div class="flex gap-2 mt-2">
                            <button type="button" id="loadTemplateBtn" class="flex-1 bg-slate-700 hover:bg-slate-800 text-white rounded-lg py-2 text-xs">Load</button>
                            <button type="button" id="deleteTemplateBtn" class="flex-1 bg-red-600 hover:bg-red-700 text-white rounded-lg py-2 text-xs">Delete</button>
                        </div>
                        <div id="templateListStatus" class="mt-2 text-xs text-gray-500 dark:text-gray-400"></div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-500 mb-2">All Templates</label>
                        <div id="allTemplateCards" class="space-y-2 max-h-56 overflow-y-auto pr-1"></div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-xs font-bold uppercase text-gray-500">Text Styling</label>
                        <input id="objectText" type="text" placeholder="{{full_name}}" class="w-full px-3 py-2 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        <select id="fontFamily" class="w-full px-3 py-2 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            <option value="Arial">Arial</option>
                            <option value="Times New Roman">Times New Roman</option>
                            <option value="Courier New">Courier New</option>
                            <option value="Georgia">Georgia</option>
                        </select>
                        <input id="fontSize" type="number" min="6" max="160" value="28" class="w-full px-3 py-2 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        <input id="objectColor" type="color" value="#111827" class="w-full h-10 rounded border dark:border-gray-600">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-500 mb-2">Dynamic Fields</label>
                        <div class="flex flex-wrap gap-1.5">
                            <?php foreach (['sanstha_name', 'authorized_person', 'auth_type', 'auth_code', 'scope_of_work', 'center_address', 'district', 'full_name', 'student_no', 'member_no', 'college_name', 'city_name', 'state_name', 'level_name', 'designation', 'phone', 'email', 'blood_group', 'address', 'date', 'doc_no', 'site_name', 'ngo_address', 'ngo_phone', 'ngo_website', 'event_title', 'event_date', 'event_location', 'occasion_name', 'achievement_position', 'certificate_title', 'issued_for', 'valid_from', 'valid_until', 'dob'] as $token): ?>
                                <button type="button" data-token="{{<?php echo $token; ?>}}" class="token-btn">{{<?php echo $token; ?>}}</button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div id="previewPanel" class="hidden">
                        <label class="block text-xs font-bold uppercase text-gray-500 mb-2">Preview</label>
                        <img id="previewImage" alt="Template preview" class="w-full rounded-lg border dark:border-gray-700">
                    </div>
                </aside>
            </div>
        </main>
    </div>
</div>

<style>
    .tb-tool {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .35rem;
        min-height: 2.4rem;
        border: 1px solid #e5e7eb;
        border-radius: .5rem;
        font-size: .75rem;
        color: #374151;
        background: #fff;
    }
    .dark .tb-tool {
        background: #374151;
        border-color: #4b5563;
        color: #f3f4f6;
    }
    .tb-icon {
        width: 2rem;
        height: 2rem;
        border: 1px solid #e5e7eb;
        border-radius: .5rem;
        background: #fff;
    }
    .dark .tb-icon {
        background: #374151;
        border-color: #4b5563;
        color: #f3f4f6;
    }
    .token-btn {
        border: 1px solid #dbeafe;
        border-radius: 999px;
        padding: .25rem .5rem;
        font-size: .68rem;
        color: #1d4ed8;
        background: #eff6ff;
    }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
<script>
    const csrfToken = <?php echo json_encode($_SESSION['csrf_token']); ?>;
    const canvas = new fabric.Canvas('templateCanvas', {
        preserveObjectStacking: true,
        backgroundColor: '#ffffff'
    });
    const placeholderSizes = {
        photo: [170, 210],
        qr: [145, 145],
        logo: [180, 90],
        signature: [220, 90]
    };

    function activeObject() {
        return canvas.getActiveObject();
    }

    function setCanvasSize(w, h) {
        w = Math.max(200, Math.min(3000, parseInt(w || 856, 10)));
        h = Math.max(200, Math.min(3000, parseInt(h || 540, 10)));
        canvas.setWidth(w);
        canvas.setHeight(h);
        document.getElementById('canvasWrap').style.width = w + 'px';
        document.getElementById('canvasWrap').style.height = h + 'px';
        canvas.requestRenderAll();
    }

    function applyTypeSize() {
        const type = document.getElementById('templateType').value;
        const sizes = {
            id_card: [856, 540],
            receipt: [794, 1123],
            membership_certificate: [1123, 794],
            achievement_certificate: [1123, 794],
            appointment_letter: [794, 1123],
            visitor_certificate: [1123, 794],
            volunteer_certificate: [1123, 794],
            student_certificate: [1123, 794],
            sanstha_authorization: [1123, 794]
        };
        document.getElementById('canvasWidth').value = sizes[type][0];
        document.getElementById('canvasHeight').value = sizes[type][1];
        setCanvasSize(sizes[type][0], sizes[type][1]);
        loadTemplateList();
    }

    function addPlaceholder(kind, label) {
        const size = placeholderSizes[kind] || [180, 120];
        const rect = new fabric.Rect({
            left: 0,
            top: 0,
            width: size[0],
            height: size[1],
            fill: '#f8fafc',
            stroke: '#2563eb',
            strokeWidth: 2,
            strokeDashArray: [8, 6],
            rx: 4,
            ry: 4
        });
        const text = new fabric.Text(label, {
            left: size[0] / 2,
            top: size[1] / 2,
            originX: 'center',
            originY: 'center',
            fontFamily: 'Arial',
            fontSize: kind === 'qr' ? 22 : 24,
            fontWeight: 'bold',
            fill: '#1d4ed8',
            selectable: false,
            evented: false
        });
        const group = new fabric.Group([rect, text], {
            left: 60,
            top: 60,
            width: size[0],
            height: size[1],
            placeholderType: kind,
            placeholderLabel: label,
            objectCaching: false
        });
        canvas.add(group).setActiveObject(group);
        canvas.requestRenderAll();
    }

    function addElement(type) {
        if (type === 'text') {
            canvas.add(new fabric.Textbox('{{full_name}}', {
                left: 80,
                top: 80,
                width: 260,
                fontSize: 28,
                fill: '#111827',
                fontFamily: 'Arial'
            }));
        } else if (['photo', 'qr', 'logo', 'signature'].includes(type)) {
            addPlaceholder(type, type.toUpperCase());
        } else if (type === 'rect') {
            canvas.add(new fabric.Rect({ left: 80, top: 80, width: 180, height: 90, fill: '#ffffff', stroke: '#111827', strokeWidth: 2 }));
        } else if (type === 'circle') {
            canvas.add(new fabric.Circle({ left: 80, top: 80, radius: 55, fill: '#e0f2fe', stroke: '#0284c7', strokeWidth: 2 }));
        } else if (type === 'line') {
            canvas.add(new fabric.Line([50, 50, 260, 50], { left: 80, top: 80, stroke: '#111827', strokeWidth: 3 }));
        }
        canvas.requestRenderAll();
    }

    function syncControls() {
        const obj = activeObject();
        if (!obj) return;
        const textInput = document.getElementById('objectText');
        const fontFamily = document.getElementById('fontFamily');
        const fontSize = document.getElementById('fontSize');
        if (obj.type === 'textbox' || obj.type === 'i-text' || obj.type === 'text') {
            textInput.disabled = false;
            fontFamily.disabled = false;
            fontSize.disabled = false;
            textInput.value = obj.text || '';
            fontFamily.value = obj.fontFamily || 'Arial';
            fontSize.value = obj.fontSize || 28;
            document.getElementById('objectColor').value = obj.fill || '#111827';
        } else {
            textInput.disabled = true;
            fontFamily.disabled = true;
            fontSize.disabled = true;
            const label = obj.placeholderLabel || obj.placeholderType || obj.type || 'Object';
            textInput.value = label.toString().toUpperCase();
            document.getElementById('objectColor').value = obj.fill && /^#/.test(obj.fill) ? obj.fill : '#111827';
        }
    }

    function loadTemplateList(selectedId = '') {
        const type = document.getElementById('templateType').value;
        const list = document.getElementById('templateList');
        const status = document.getElementById('templateListStatus');
        const cards = document.getElementById('allTemplateCards');
        status.textContent = 'Loading templates...';

        fetch(`actions/list-templates.php?type=${encodeURIComponent(type)}`, { cache: 'no-store' })
            .then(r => r.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Unable to load templates.');
                list.innerHTML = '<option value="">Select template</option>';
                (data.templates || []).forEach(t => {
                    const opt = document.createElement('option');
                    opt.value = t.id;
                    opt.textContent = `${t.template_name} ${t.status == 1 ? '(Active)' : '(Inactive)'}`;
                    list.appendChild(opt);
                });
                if (selectedId) list.value = String(selectedId);
                status.textContent = (data.templates || []).length ? `${data.templates.length} template(s) for this document type.` : 'No templates saved for this document type.';
            })
            .catch(err => {
                list.innerHTML = '<option value="">Select template</option>';
                status.textContent = err.message || 'Unable to load templates.';
            });

        fetch('actions/list-templates.php', { cache: 'no-store' })
            .then(r => r.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Unable to load templates.');
                cards.innerHTML = '';
                const templates = data.templates || [];
                if (!templates.length) {
                    cards.innerHTML = '<div class="text-xs text-gray-500 dark:text-gray-400 rounded-lg border dark:border-gray-700 p-3">No saved templates yet.</div>';
                    return;
                }
                templates.forEach(t => {
                    const card = document.createElement('button');
                    card.type = 'button';
                    card.className = 'w-full text-left rounded-lg border dark:border-gray-700 p-2 hover:bg-gray-50 dark:hover:bg-gray-700 transition';
                    card.innerHTML = `<div class="text-xs font-semibold text-gray-800 dark:text-white">${escapeHtml(t.template_name)}</div><div class="text-[11px] text-gray-500 dark:text-gray-400">${escapeHtml(t.template_type.replaceAll('_', ' '))} · ${t.status == 1 ? 'Active' : 'Inactive'}</div>`;
                    card.addEventListener('click', () => loadTemplate(t.id));
                    cards.appendChild(card);
                });
            })
            .catch(() => {
                cards.innerHTML = '<div class="text-xs text-red-600 rounded-lg border border-red-200 p-3">Unable to load templates.</div>';
            });
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    function loadTemplate(id) {
        if (!id) return;
        fetch(`actions/load-template.php?id=${encodeURIComponent(id)}`)
            .then(r => r.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Unable to load template.');
                const t = data.template;
                document.getElementById('templateId').value = t.id;
                document.getElementById('templateName').value = t.template_name;
                document.getElementById('templateType').value = t.template_type;
                document.getElementById('canvasWidth').value = t.canvas_width;
                document.getElementById('canvasHeight').value = t.canvas_height;
                document.getElementById('existingBackground').value = t.background_image || '';
                setCanvasSize(t.canvas_width, t.canvas_height);
                canvas.loadFromJSON(t.json_data, () => {
                    if (t.background_image) {
                        fabric.Image.fromURL('../' + t.background_image, img => {
                            canvas.setBackgroundImage(img, canvas.renderAll.bind(canvas), {
                                scaleX: canvas.width / img.width,
                                scaleY: canvas.height / img.height
                            });
                        }, { crossOrigin: 'anonymous' });
                    } else {
                        canvas.setBackgroundImage(null, canvas.renderAll.bind(canvas));
                    }
                    canvas.requestRenderAll();
                });
            })
            .catch(err => Alpine.store('toast').show(err.message, 'error'));
    }

    function saveTemplate() {
        const form = new FormData();
        form.append('csrf_token', csrfToken);
        form.append('id', document.getElementById('templateId').value);
        form.append('template_name', document.getElementById('templateName').value);
        form.append('template_type', document.getElementById('templateType').value);
        form.append('canvas_width', canvas.width);
        form.append('canvas_height', canvas.height);
        const templateJson = canvas.toJSON(['placeholderType', 'placeholderLabel']);
        delete templateJson.backgroundImage;
        form.append('json_data', JSON.stringify(templateJson));
        form.append('existing_background_image', document.getElementById('existingBackground').value);
        form.append('status', '1');
        const bg = document.getElementById('backgroundUpload').files[0];
        if (bg) form.append('background_image', bg);

        fetch('actions/save-template.php', { method: 'POST', body: form })
            .then(r => r.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Save failed.');
                document.getElementById('templateId').value = data.id;
                document.getElementById('existingBackground').value = data.background_image || '';
                loadTemplateList(data.id);
                Alpine.store('toast').show(data.message, 'success');
            })
            .catch(err => Alpine.store('toast').show(err.message, 'error'));
    }

    document.querySelectorAll('[data-add]').forEach(btn => btn.addEventListener('click', () => addElement(btn.dataset.add)));
    document.querySelectorAll('[data-token]').forEach(btn => btn.addEventListener('click', () => {
        const obj = activeObject();
        if (obj && ['textbox', 'i-text', 'text'].includes(obj.type)) {
            obj.set('text', (obj.text || '') + btn.dataset.token);
            canvas.requestRenderAll();
            syncControls();
        }
    }));
    document.getElementById('templateType').addEventListener('change', applyTypeSize);
    document.getElementById('canvasWidth').addEventListener('change', e => setCanvasSize(e.target.value, canvas.height));
    document.getElementById('canvasHeight').addEventListener('change', e => setCanvasSize(canvas.width, e.target.value));
    document.getElementById('saveTemplateBtn').addEventListener('click', saveTemplate);
    document.getElementById('loadTemplateBtn').addEventListener('click', () => loadTemplate(document.getElementById('templateList').value));
    document.getElementById('deleteTemplateBtn').addEventListener('click', () => {
        const id = document.getElementById('templateList').value;
        if (!id || !confirm('Delete this template?')) return;
        const form = new FormData();
        form.append('csrf_token', csrfToken);
        form.append('id', id);
        fetch('actions/delete-template.php', { method: 'POST', body: form })
            .then(r => r.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Delete failed.');
                loadTemplateList();
                Alpine.store('toast').show(data.message, 'success');
            })
            .catch(err => Alpine.store('toast').show(err.message, 'error'));
    });
    document.getElementById('backgroundUpload').addEventListener('change', e => {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = ev => fabric.Image.fromURL(ev.target.result, img => {
            canvas.setBackgroundImage(img, canvas.renderAll.bind(canvas), {
                scaleX: canvas.width / img.width,
                scaleY: canvas.height / img.height
            });
        });
        reader.readAsDataURL(file);
    });
    document.getElementById('objectText').addEventListener('input', e => {
        const obj = activeObject();
        if (obj && ['textbox', 'i-text', 'text'].includes(obj.type)) obj.set('text', e.target.value);
        canvas.requestRenderAll();
    });
    document.getElementById('fontFamily').addEventListener('change', e => {
        const obj = activeObject();
        if (obj && ['textbox', 'i-text', 'text'].includes(obj.type)) obj.set('fontFamily', e.target.value);
        canvas.requestRenderAll();
    });
    document.getElementById('fontSize').addEventListener('input', e => {
        const obj = activeObject();
        if (obj && ['textbox', 'i-text', 'text'].includes(obj.type)) obj.set('fontSize', parseInt(e.target.value, 10));
        canvas.requestRenderAll();
    });
    document.getElementById('objectColor').addEventListener('input', e => {
        const obj = activeObject();
        if (obj) obj.set(obj.type === 'line' ? 'stroke' : 'fill', e.target.value);
        canvas.requestRenderAll();
    });
    document.getElementById('bringForward').addEventListener('click', () => activeObject() && canvas.bringForward(activeObject()));
    document.getElementById('sendBackward').addEventListener('click', () => activeObject() && canvas.sendBackwards(activeObject()));
    document.getElementById('deleteObject').addEventListener('click', () => activeObject() && canvas.remove(activeObject()));
    document.getElementById('duplicateObject').addEventListener('click', () => {
        const obj = activeObject();
        if (!obj) return;
        obj.clone(clone => {
            clone.set({ left: obj.left + 18, top: obj.top + 18 });
            canvas.add(clone).setActiveObject(clone);
            canvas.requestRenderAll();
        }, ['placeholderType', 'placeholderLabel']);
    });
    document.getElementById('clearCanvas').addEventListener('click', () => confirm('Clear canvas objects?') && canvas.getObjects().forEach(o => canvas.remove(o)));
    document.getElementById('previewTemplate').addEventListener('click', () => {
        document.getElementById('previewImage').src = canvas.toDataURL({ format: 'png', multiplier: .4 });
        document.getElementById('previewPanel').classList.remove('hidden');
    });
    canvas.on('selection:created', syncControls);
    canvas.on('selection:updated', syncControls);
    applyTypeSize();
</script>

<?php require 'includes/footer.php'; ?>
