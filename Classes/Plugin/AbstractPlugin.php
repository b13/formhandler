<?php

namespace Typoheads\Formhandler\Plugin;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Page\PageInformation;

class AbstractPlugin
{
    protected ?ContentObjectRenderer $cObj = null;
    protected ?ServerRequestInterface $request = null;
    protected ?FrontendUserAuthentication $frontendUserAuthentication = null;
    protected ?PageInformation $pageInformation = null;
    protected MarkerBasedTemplateService $templateService;

    public bool $LOCAL_LANG_loaded = false;
    public string $LLkey = 'default';
    public array $LOCAL_LANG = [];
    public string $prefixId = 'Tx_Formhandler';

    public function __construct()
    {
        $this->templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
    }

    public function setRequests(ServerRequestInterface $request): void
    {
        $this->request = $request;
        $this->cObj = $this->request->getAttribute('currentContentObject');
        $this->frontendUserAuthentication = $this->request->getAttribute('frontend.user');
        $this->pageInformation = $this->request->getAttribute('frontend.page.information');
    }

    public function pi_initPIflexForm($field = 'pi_flexform')
    {
        // Converting flexform data into array
        $fieldData = $this->cObj->data[$field] ?? null;
        if (!is_array($fieldData) && $fieldData) {
            $this->cObj->data[$field] = GeneralUtility::xml2array((string)$fieldData);
            if (!is_array($this->cObj->data[$field])) {
                $this->cObj->data[$field] = [];
            }
        }
    }

    /**
     * Return value from somewhere inside a FlexForm structure
     *
     * @param array $T3FlexForm_array FlexForm data
     * @param string $fieldName Field name to extract. Can be given like "test/el/2/test/el/field_templateObject" where each part will dig a level deeper in the FlexForm data.
     * @param string $sheet Sheet pointer, eg. "sDEF
     * @param string $lang Language pointer, eg. "lDEF
     * @param string $value Value pointer, eg. "vDEF
     * @return string|null The content.
     */
    public function pi_getFFvalue($T3FlexForm_array, $fieldName, $sheet = 'sDEF', $lang = 'lDEF', $value = 'vDEF')
    {
        $sheetArray = $T3FlexForm_array['data'][$sheet][$lang] ?? '';
        if (is_array($sheetArray)) {
            return $this->pi_getFFvalueFromSheetArray($sheetArray, explode('/', $fieldName), $value);
        }
        return null;
    }

    /**
     * Returns part of $sheetArray pointed to by the keys in $fieldNameArray
     *
     * @param array $sheetArray Multidimensional array, typically FlexForm contents
     * @param array $fieldNameArr Array where each value points to a key in the FlexForms content - the input array will have the value returned pointed to by these keys. All integer keys will not take their integer counterparts, but rather traverse the current position in the array and return element number X (whether this is right behavior is not settled yet...)
     * @param string $value Value for outermost key, typ. "vDEF" depending on language.
     * @return mixed The value, typ. string.
     * @internal
     * @see pi_getFFvalue()
     */
    public function pi_getFFvalueFromSheetArray($sheetArray, $fieldNameArr, $value)
    {
        $tempArr = $sheetArray;
        foreach ($fieldNameArr as $k => $v) {
            if (MathUtility::canBeInterpretedAsInteger($v)) {
                if (is_array($tempArr)) {
                    $c = 0;
                    foreach ($tempArr as $values) {
                        if ($c == $v) {
                            $tempArr = $values;
                            break;
                        }
                        $c++;
                    }
                }
            } elseif (isset($tempArr[$v])) {
                $tempArr = $tempArr[$v];
            }
        }
        return $tempArr[$value] ?? '';
    }

    /**
     * Loads local-language values from the file passed as a parameter or
     * by looking for a "locallang" file in the
     * plugin class directory ($this->scriptRelPath).
     * Also locallang values set in the TypoScript property "_LOCAL_LANG" are
     * merged onto the values found in the "locallang" file.
     * Supported file extensions xlf
     *
     * @param string $languageFilePath path to the plugin language file in format EXT:....
     */
    public function pi_loadLL($languageFilePath = '')
    {
        if ($this->LOCAL_LANG_loaded) {
            return;
        }
        if ($languageFilePath !== '') {
            $languageFactory = GeneralUtility::makeInstance(LocalizationFactory::class);
            $this->LOCAL_LANG = $languageFactory->getParsedData($languageFilePath, $this->LLkey);
            // Overlaying labels from TypoScript (including fictitious language keys for non-system languages!):
            if (isset($this->conf['_LOCAL_LANG.'])) {
                // Clear the "unset memory"
                //$this->LOCAL_LANG_UNSET = [];
                foreach ($this->conf['_LOCAL_LANG.'] as $languageKey => $languageArray) {
                    // Remove the dot after the language key
                    $languageKey = substr($languageKey, 0, -1);
                    // Don't process label if the language is not loaded
                    if (is_array($languageArray) && isset($this->LOCAL_LANG[$languageKey])) {
                        foreach ($languageArray as $labelKey => $labelValue) {
                            if (!is_array($labelValue)) {
                                $this->LOCAL_LANG[$languageKey][$labelKey][0]['target'] = $labelValue;
                                if ($labelValue === '') {
                                    //$this->LOCAL_LANG_UNSET[$languageKey][$labelKey] = '';
                                }
                            }
                        }
                    }
                }
            }
        }
        $this->LOCAL_LANG_loaded = true;
    }

    /***************************
     *
     * Link functions
     *
     **************************/
    /**
     * Get URL to some page.
     * Returns the URL to page $id with $target and an array of additional url-parameters, $urlParameters
     * Simple example: $this->pi_getPageLink(123) to get the URL for page-id 123.
     *
     * The function basically calls $this->cObj->getTypoLink_URL()
     *
     * @param int $id Page id
     * @param string $target Target value to use. Affects the &type-value of the URL, defaults to current.
     * @param array|string $urlParameters As an array key/value pairs represent URL parameters to set. Values NOT URL-encoded yet, keys should be URL-encoded if needed. As a string the parameter is expected to be URL-encoded already.
     * @return string The resulting URL
     * @see pi_linkToPage()
     * @see ContentObjectRenderer::createUrl()
     */
    public function pi_getPageLink($id, $target = '', $urlParameters = [])
    {
        $conf = [
            'parameter' => $id,
        ];
        if ($target) {
            $conf['target'] = $target;
            $conf['extTarget'] = $target;
            $conf['fileTarget'] = $target;
        }
        if (is_array($urlParameters)) {
            if (!empty($urlParameters)) {
                $conf['additionalParams'] = HttpUtility::buildQueryString($urlParameters, '&');
            }
        } else {
            $conf['additionalParams'] = $urlParameters;
        }
        return $this->cObj->createUrl($conf);
    }

    public function pi_wrapInBaseClass($str)
    {
        $content = '<div class="' . str_replace('_', '-', $this->prefixId) . '">
		' . $str . '
	</div>
	';
        return $content;
    }
}
