<?php

use App\Controller\DefaultController;

function getCustomFields(array $customFields, string $language, array $entity, string $entityName): string
{
    $customFieldsHtml = '';
    foreach ($customFields as $customField) {
        $name = $customField['name'];
        $label = $customField['config']['label'][$language]
                ?? $customField['config']['label'][DefaultController::DEFAULT_LANGUAGE]
            ?? $name;
        $customFieldsHtml .= ' <h5 class="m-3">' . htmlspecialchars($label) . '</h5>';
        $id = $entityName . '-customFields-' . $name;
        $customFieldsHtml .= '<textarea name="customFields[' . htmlspecialchars($name) . ']" id="' . htmlspecialchars($id) .'">'
            . htmlspecialchars($entity['customFields'][$name] ?? '')
            . '</textarea>';
    }
    return $customFieldsHtml;
}