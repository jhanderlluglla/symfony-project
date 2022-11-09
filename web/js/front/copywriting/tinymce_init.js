tinymce.init({
    height: 700,
    selector: '#copywriting_article_text, .tinymce',
    content_css : "/css/admin/wysiwyg.css",
    formats: {
        alignleft: [{
            selector: "figure.image",
            collapsed: !1,
            classes: "alignleft",
            ceFalseOverride: !0,
            preview: "font-family font-size"
        }, {
            selector: "figure,p,h1,h2,h3,h4,h5,h6,td,th,tr,div,ul,ol,li",
            classes: "alignleft",
            inherit: !1,
            preview: !1,
            defaultBlock: "div"
        }, {
            selector: "img,table",
            collapsed: !1,
            classes: "alignleft",
            preview: "font-family font-size"
        }],
        aligncenter: [{
            selector: "figure,p,h1,h2,h3,h4,h5,h6,td,th,tr,div,ul,ol,li",
            classes: "aligncenter",
            inherit: !1,
            preview: "font-family font-size",
            defaultBlock: "div"
        }, {
            selector: "figure.image",
            collapsed: !1,
            classes: "aligncenter",
            ceFalseOverride: !0,
            preview: "font-family font-size"
        }, {
            selector: "img",
            collapsed: !1,
            classes: "aligncenter",
            preview: !1
        }, {
            selector: "table",
            collapsed: !1,
            classes: "aligncenter",
            preview: "font-family font-size"
        }],
        alignright: [{
            selector: "figure.image",
            collapsed: !1,
            classes: "alignright",
            ceFalseOverride: !0,
            preview: "font-family font-size"
        }, {
            selector: "figure,p,h1,h2,h3,h4,h5,h6,td,th,tr,div,ul,ol,li",
            classes: "alignright",
            inherit: !1,
            preview: "font-family font-size",
            defaultBlock: "div"
        }, {selector: "img,table", collapsed: !1, styles: {"float": "right"}, preview: "font-family font-size"}],
        removeformat: [
            {selector: '*', attributes : ['id', 'style', 'class'], split : false, expand : false, deep : true}
        ]
    },
    plugins: [
        // "advcode linkchecker powerpaste", //premium plugins
        "advlist autolink lists link image charmap print preview anchor",
        "searchreplace visualblocks code fullscreen",
        "insertdatetime media table contextmenu imagetools wordcount",
        "textcolor paste colorpicker"
    ],
    toolbar: "insertfile undo redo | styleselect | bold italic underline strikethrough forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image blockquote | pixabay | pastetext removeformat",
    powerpaste_word_import: "clean",
    powerpaste_html_import: "clean",
    image_caption: true,
    paste_auto_cleanup_on_paste : true,
    paste_remove_spans: true,
    paste_remove_styles: true,
    paste_strip_class_attributes: "all",
    paste_retain_style_properties: "",
    convert_urls: false,
    invalid_elements: "div",
    forced_root_block : false,
    images_upload_handler: function (blobInfo, success, failure) {
        uploadImage(blobInfo.blob(), function (response) {
            success(response.url);
        });
    },
    paste_postprocess: function(plugin, args) {
        $(args.node).find("li>p").each(function (index, element) {
            $(element).contents().unwrap();
        });
        $(args.node).find("*").each(function (index, element) {
            $(element).removeAttr("style");
            $(element).removeAttr("class");
            if(element.nodeName === "SPAN"){
                $(element).contents().unwrap();
            }
        });
    },
    setup: function(ed) {
        ed.addButton('pixabay', {
            title : 'Pixabay',
            image : '/img/pixabay.png',
            onclick: function () {
                showPixabayModal(function (url) {
                    ed.execCommand('mceInsertContent', false, '<img src="' + url + '"/>');
                });
            },
        });
    }
});

function getEditorContent(){
    return tinyMCE.activeEditor.getContent();
}