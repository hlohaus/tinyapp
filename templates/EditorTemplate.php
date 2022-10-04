<?php

use App\Controller\DefaultController;

const BOOTSTRAP_URL = 'https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css';
?>
<!DOCTYPE html>
<html>
<head>
    <link href="<?= BOOTSTRAP_URL; ?>"
          rel="stylesheet"
          integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx"
          crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" referrerpolicy="origin"
          href="https://rawcdn.githack.com/shopware/administration/v6.4.13.0/Resources/public/static/css/app.css">
    <script src="https://cdn.tiny.cloud/1/v9t1p0dfcakgtfn8axp7cdm690a7b30yx89866n2a5hde62p/tinymce/6/tinymce.min.js"
            referrerpolicy="origin"></script>
    <style>
        body {
            padding-bottom: 58px;
        }

        form {
            height: 100%;
        }

        .content {
            height: 100%;
            overflow-y: scroll;
        }

        .smart-bar__actions {
            border-top: 2px solid rgb(87, 217, 163);
            z-index: 1400;
        }

        .sidebar-button-group {
            width: 10%;
            display: inline-block;
        }

        .sidebar-open .sidebar-button-group {
            width: 25%;
        }
        .tox-fullscreen .sidebar-button-group {
            width: 25%;
        }

        .sidebar.col {
            width: 10%;
            flex: 0 0 auto;
            transition: width 200ms;
        }

        .tox .tox-dialog-wrap {
            max-width: 75%;
            padding-bottom: 24px;
        }

        .tox-fullscreen .tox.tox-tinymce.tox-fullscreen {
            max-width: 75%;
            padding-bottom: 42px;
        }

        .sidebar.col:hover {
            width: 25%;
        }

        .sidebar-open .sidebar {
            width: 25%;
        }

        .tox-fullscreen .sidebar {
            width: 25%;
        }

        body.dark {
            background-color: #6c757d;
            color: #dee2e6;
        }
    </style>
</head>
<body class="">
<div class="row h-100">
    <div class="col content w-75 pr-0 pl-2">
        <form method="post" id="content">
            <textarea name="description"
                      id="description"><?= htmlspecialchars($entity['description'] ?? ''); ?></textarea>
            <?php DefaultController::renderTemplate('CustomFields', $form ?? []); ?>
        </form>

        <div class="smart-bar__actions position-fixed bottom-0 w-100">
            <span class="p-2" style="text-align: right; display: block;">
                <?php
                    $optionLabels = [
                        'darkMode' => 'Dark mode',
                        'allowJavascript' => 'Allow Javascript',
                        'spellCheck' => 'Spell check'
                    ];
                    foreach ($optionLabels as $option => $optionLabel) {
                ?>
                <label for="<?= $option; ?>" class="mx-4">
                    <?= htmlspecialchars($optionLabel); ?>:
                    <input type="hidden" name="options[<?= $option; ?>]" value="0" form="content">
                    <input type="checkbox" id="<?= $option; ?>" name="options[<?= $option; ?>]" value="1"  onclick="setOption(this)" form="content">
                </label>
                <?php
                    }
                ?>
                <button class="sw-button sw-button-process sw-button--primary" form="content">
                    <span class="sw-button__content"><span class="sw-button-process__content">
                        Save
                    </span></span>
                </button>
                <div class="sidebar-button-group">
                    <form target="sidebar" method="post" action="<?= htmlspecialchars($sidebarUrl ?? ''); ?>">
                        <label for="typeFilter">
                            Typ
                            <select id="fileType" name="type" onchange="this.form.submit()">
                                <option>Bitte wählen...</option>
                                <option value="image">Bilder</option>
                                <option value="media">Musik&amp;Videos</option>
                                <option value="file">Dokumente</option>
                            </select>
                        </label>
                    </form>
                </div>
            </span>
        </div>
    </div>
    <div class="col sidebar">
        <iframe name="sidebar" class="w-100 h-100" src="<?= htmlspecialchars($sidebarUrl ?? ''); ?>"></iframe>
    </div>
</div>
</form>
<script>
    const options = <?= json_encode($options ?? []); ?>;
    for(let key in options) {
        if (options[key].length && options[key] === '0') {
            options[key] = false;
        }
        document.getElementById(key).checked = options[key];
    }
    //const darkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const fileType = document.getElementById('fileType');
    if (options.darkMode) {
        document.body.classList.add('dark');
    }
    const isSmallScreen = window.matchMedia('(max-width: 1023.5px)').matches;
    const lang = '<?= htmlspecialchars(substr($language ?? '', 0, 2)) ?>';
    let filePickerCallback = null;

    tinymce.init({
        selector: 'textarea',
        language: lang,
        relative_urls: false,
        document_base_url: '<?= htmlspecialchars($shopUrl ?? '');?>',
        plugins: 'iconfonts print preview paste importcss searchreplace autolink' +
            ' autosave save directionality code visualblocks visualchars' +
            ' fullscreen image link media template codesample table charmap' +
            ' hr pagebreak nonbreaking anchor toc insertdatetime advlist' +
            ' lists wordcount imagetools textpattern noneditable help charmap' +
            ' quickbars emoticons',
        // plugins: 'preview powerpaste casechange importcss searchreplace autolink autosave save directionality advcode visualblocks visualchars '
        //     + 'fullscreen image link media mediaembed template codesample table charmap pagebreak nonbreaking anchor '
        //     + 'tableofcontents insertdatetime advlist lists checklist wordcount tinymcespellchecker a11ychecker editimage help '
        //     + 'formatpainter permanentpen pageembed charmap tinycomments mentions quickbars linkchecker emoticons advtable export',
        menu: {
            tc: {
                title: 'Comments',
                items: 'addcomment showcomments deleteallconversations'
            }
        },
        menubar: 'file edit view insert format tools table tc help',
        toolbar: 'undo redo | restoredraft | bold italic underline strikethrough | fontfamily fontsize blocks | '
            + 'alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist checklist | '
            + 'forecolor backcolor casechange permanentpen formatpainter removeformat | pagebreak | charmap emoticons | '
            + 'fullscreen  preview save print | insertfile image media pageembed template link anchor codesample | '
            + 'a11ycheck ltr rtl | showcomments addcomment | auto_focus',
        // toolbar_sticky: true,
        // autosave_ask_before_unload: true,
        // autosave_interval: '30s',
        autosave_prefix: '<?= htmlspecialchars(($entityName ?? '') . '-' . ($entity['id'] ?? '')); ?>-{id}',
        // autosave_restore_when_empty: false,
        // autosave_retention: '2m',
        image_advtab: true,
        link_list: [
            {title: 'My page 1', value: 'Responsive image'},
            {title: 'My page 2', value: 'http://www.moxiecode.com'}
        ],
        image_class_list: [
            {title: 'None', value: ''},
            {title: 'Responsive image', value: 'img-fluid'},
            {title: 'Small border', value: 'img-thumbnail'},
            {title: 'Left', value: 'float-left'},
            {title: 'Center', value: 'rounded mx-auto d-block'},
            {title: 'Right', value: 'float-right'}
        ],
        importcss_append: true,
        templates: [
            {
                title: 'New Table',
                description: 'creates a new table',
                content: '<div class="mceTmpl"><table width="98%%"  border="0" cellspacing="0" cellpadding="0"><tr><th scope="col"> </th><th scope="col"> </th></tr><tr><td> </td><td> </td></tr></table></div>'
            },
            {title: 'Starting my story', description: 'A cure for writers block', content: 'Once upon a time...'},
            {
                title: 'New list with dates',
                description: 'New List with dates',
                content: '<div class="mceTmpl"><span class="cdate">cdate</span><br><span class="mdate">mdate</span><h2>My List</h2><ul><li></li><li></li></ul></div>'
            }
        ],
        template_cdate_format: '[Date Created (CDATE): %m/%d/%Y : %H:%M:%S]',
        template_mdate_format: '[Date Modified (MDATE): %m/%d/%Y : %H:%M:%S]',
        height: 450,
        image_caption: true,
        quickbars_selection_toolbar: 'bold italic | quicklink h2 h3 blockquote quickimage quicktable',
        noneditable_class: 'mceNonEditable',
        toolbar_mode: 'sliding',
        spellchecker_ignore_list: ['Ephox', 'Moxiecode'],
        tinycomments_mode: 'embedded',
        browser_spellcheck: options.spellCheck,
        contextmenu: options.spellCheck ? false : 'link image editimage table configurepermanentpen',
        a11y_advanced_options: true,
        skin: options.darkMode ? 'oxide-dark' : 'oxide',
        content_css: options.darkMode ? 'dark' : '<?= BOOTSTRAP_URL; ?>',
        allow_script_urls: options.allowJavascript,
        extended_valid_elements: options.allowJavascript ? 'script[src|async|defer|type|charset|crossorigin]' : false,
        file_picker_callback: function (callback, value, meta) {
            filePickerCallback = callback;
            document.body.classList.add('sidebar-open');
            fileType.value = meta.filetype;
        },
        setup: function(editor) {
            editor.on('CloseWindow', function(e) {
                filePickerCallback = null;
            });
        }
    });

    window.addEventListener('message', (event) => {
        if (event.origin !== location.origin && event.data.url) {
            return;
        }
        if (filePickerCallback) {
            filePickerCallback(event.data.url, {
                alt: event.data.alt,
                title: event.data.title
            });
        } else {
            const attrs = {
                url: event.data.url,
                alt: event.data.alt,
                title: event.data.title,
                text: event.data.text
            };
            applyNode(attrs);
        }
        document.body.classList.remove('sidebar-open');
    }, false);

    function applyNode(attrs) {
        const node = tinymce.activeEditor.selection.getNode();
        if (node && node.nodeName === 'img') {
            for(let key in attrs) {
                node.setAttribute(key, attrs[key]);
            }
        } else {
            const urlKey = attrs.image ? 'src' : 'href';
            const nodeName = attrs.image ? 'img' : 'a';
            attrs[urlKey] = attrs.url;
            const node = tinymce.activeEditor.dom.create(nodeName, attrs);
            if (nodeName === 'a') {
                node.innerText = attrs.text || attrs.title
            }
            tinymce.activeEditor.selection.setNode(node);
        }
    }

    function setOption(el) {
        switch (el.id) {
            case 'darkMode':
                setDarkMode(el);
                break;
            default:
                el.form.submit();
                break;
        }
    }

    function setDarkMode(el) {
        const darkMode = el.checked;
        if (darkMode) {
            document.body.classList.add('dark');
            const ed = new tinymce.Editor('description', {
                skin: 'oxide-dark',
                content_css: 'dark',
            }, tinymce.EditorManager);
            ed.render();
        } else {
            document.body.classList.remove('dark');
            el.form.submit();
        }
    }
</script>
</body>
</html>