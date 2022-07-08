<?php declare(strict_types=1);

namespace App\Controller;

use App\WebService\AdminApi;
use Shopware\AppBundle\Shop\ShopRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class EditorController extends AbstractController
{
    public function __construct(
        private ShopRepositoryInterface $shopRepository,
        private AdminApi $adminApi
    ) {
    }

    #[Route('/edit-product', methods: ['POST'])]
    public function editProduct(Request $request): JsonResponse
    {
        $requestContent = json_decode($request->getContent(), true);

        $id = $requestContent['data']['ids'][0] ?? null;
        $entity = $requestContent['data']['entity'] ?? null;
        $shop = $this->shopRepository->getShopFromId($requestContent['source']['shopId']);

        $response = new JsonResponse([
            'actionType' => 'openModal',
            'payload' => [
                'iframeUrl' => 'http://localhost/editor-page?' . http_build_query([
                    'entity' => $entity,
                    'entityId' => $id,
                ]), //$this->generateUrl('editor-page'),
                'size' => 'medium',
                'expand' => true
            ]
        ]);

        $secret = $shop->getShopSecret();
        $hmac = hash_hmac('sha256', $response->getContent(), $secret);
        $response->headers->set('shopware-app-signature', $hmac);

        return $response;
    }

    #[Route('/editor-page', name: 'editor-page', methods: ['GET'])]
    public function editorPage(Request $request): Response
    {
        $shop = $this->shopRepository->getShopFromId($request->get('shop-id'));
        $languageId = $request->get('sw-context-language');
        $product = $this->adminApi->getProduct($shop, $request->get('entityId'), $languageId);
        $language = $request->get('sw-user-language');
        $languageShort = substr($language, 0, 2);
        $value = htmlspecialchars($product['description'] ?? '');

        $customFields = $this->adminApi->getEditorCustomFields($shop, $languageId);

        $customFieldsHtml = '';
        foreach ($customFields as $customField) {
            $label = $customField['config']['label'][$language] ?? $customField['config']['label']['en-GB'];
            $name = $customField['name'];
            $customFieldsHtml .= ' <h3>' . htmlspecialchars($label) . '</h3>';
            $customFieldsHtml .= '<textarea name="' . htmlspecialchars($name) . '">'
                . htmlspecialchars($product['customFields'][$name]?? '')
                .'</textarea>';
        }

        return new Response(<<<EOT
<!DOCTYPE html>
<html>
<head>
  <script src="https://cdn.tiny.cloud/1/v9t1p0dfcakgtfn8axp7cdm690a7b30yx89866n2a5hde62p/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
</head>
<body>
  <textarea name="description">$value</textarea>
  $customFieldsHtml
  <script>
    const me = this;
    const spellcheck = false;
    const useDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const isSmallScreen = window.matchMedia('(max-width: 1023.5px)').matches;
    const lang = '$languageShort';
tinymce.init({
  selector: 'textarea',
  language: lang,
  plugins: 'preview powerpaste casechange importcss searchreplace autolink autosave save directionality advcode visualblocks visualchars '
  + 'fullscreen image link media mediaembed template codesample table charmap pagebreak nonbreaking anchor '
  + 'tableofcontents insertdatetime advlist lists checklist wordcount tinymcespellchecker a11ychecker editimage help '
  + 'formatpainter permanentpen pageembed charmap tinycomments mentions quickbars linkchecker emoticons advtable export',
  menu: {
    tc: {
      title: 'Comments',
      items: 'addcomment showcomments deleteallconversations'
    }
  },
  menubar: 'file edit view insert format tools table tc help',
  toolbar: 'undo redo | bold italic underline strikethrough | fontfamily fontsize blocks | '
  + 'alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist checklist | '
  + 'forecolor backcolor casechange permanentpen formatpainter removeformat | pagebreak | charmap emoticons | '
  + 'fullscreen  preview save print | insertfile image media pageembed template link anchor codesample | a11ycheck ltr rtl | showcomments addcomment',
  // toolbar_sticky: true,
  // autosave_ask_before_unload: true,
  // autosave_interval: '30s',
  // autosave_prefix: '{path}{query}-{id}-',
  // autosave_restore_when_empty: false,
  // autosave_retention: '2m',
  image_advtab: true,
  link_list: [
    { title: 'My page 1', value: 'https://www.tiny.cloud' },
    { title: 'My page 2', value: 'http://www.moxiecode.com' }
  ],
  image_list: [
    { title: 'My page 1', value: 'https://www.tiny.cloud' },
    { title: 'My page 2', value: 'http://www.moxiecode.com' }
  ],
  image_class_list: [
    { title: 'None', value: '' },
    { title: 'Some class', value: 'class-name' }
  ],
  browser_spellcheck: spellcheck,
  importcss_append: true,
  templates: [
    { title: 'New Table', description: 'creates a new table', content: '<div class="mceTmpl"><table width="98%%"  border="0" cellspacing="0" cellpadding="0"><tr><th scope="col"> </th><th scope="col"> </th></tr><tr><td> </td><td> </td></tr></table></div>' },
    { title: 'Starting my story', description: 'A cure for writers block', content: 'Once upon a time...' },
    { title: 'New list with dates', description: 'New List with dates', content: '<div class="mceTmpl"><span class="cdate">cdate</span><br><span class="mdate">mdate</span><h2>My List</h2><ul><li></li><li></li></ul></div>' }
  ],
  template_cdate_format: '[Date Created (CDATE): %m/%d/%Y : %H:%M:%S]',
  template_mdate_format: '[Date Modified (MDATE): %m/%d/%Y : %H:%M:%S]',
  height: 450,
  image_caption: true,
  quickbars_selection_toolbar: 'bold italic | quicklink h2 h3 blockquote quickimage quicktable',
  noneditable_class: 'mceNonEditable',
  toolbar_mode: 'sliding',
  spellchecker_ignore_list: ['Ephox', 'Moxiecode'],
  relative_urls: false,
  tinycomments_mode: 'embedded',
  contextmenu: spellcheck ? false : 'link image editimage table configurepermanentpen',
  a11y_advanced_options: true,
  skin: useDarkMode ? 'oxide-dark' : 'oxide',
  content_css: useDarkMode ? 'dark' : 'default',
  extended_valid_elements: 'script[src|async|defer|type|charset|crossorigin]',
  file_picker_callback: function (callback, value, meta) {
    /* Provide file and text for the link dialog */
    me.mediaModalIsOpen = true;
    me.filePickerCallback = callback;
    me.filePickerMeta = meta;
  },
});
  </script>
</body>
</html>
EOT);
    }
}