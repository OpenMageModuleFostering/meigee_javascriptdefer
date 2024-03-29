<?php
/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category    Meigee
 * @package     Meigee_JavascriptDefer
 * @copyright   Copyright (c) 2015 Meigee Sp.Z.o.o (https://www.meigeeteam.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Meigee_JavascriptDefer_Model_Observer_Html
{
    public function replaceJs($observer)
    {
        if (!Mage::app()->getRequest()->getParam('isAjax')
            && (bool)Mage::getStoreConfig('meigee/js/enable_javascriptDefer'))
        {
            $response = $observer->getData('response');
            $html = $response->getBody();
            $js_arr = array();

            $pattern = "/((<\!--\[if lt IE(.*)<\!\[endif\]-->)|(<script*(.*)<\/script>))/isU";
            preg_match_all($pattern, $html, $matches);
            $base_adr = str_replace(array('https://','http://'), '', Mage::getConfig()->substDistroServerVars('{{base_url}}'));
            $base_adr_arr = explode('/', $base_adr);
            $base_adr =  $base_adr_arr[0];

            $is_use_ext_sources = (bool)Mage::getStoreConfig('meigee/js/enable_javascriptDefer_external_sources');
            foreach($matches[0] AS $js)
            {
                if (false !== strpos($js, '<script'))
                {
                    $html = str_replace($js, '', $html);
                    preg_match('/<* src=(("|\')[^"]*("|\'))>/i',$js, $src_arr);
                    if ($is_use_ext_sources && $src_arr && false === strpos($src_arr[1], $base_adr))
                    {
                        $js_arr[] = '
                        <script type="text/javascript">
                        //<![CDATA[
                            var script =document.createElement("script");
                            script.src = "'.trim($src_arr[1], "'\"").'";
                            script.type = "text/javascript";
                            document.getElementsByTagName("head")[0].appendChild(script);
                        //]]>
                        </script>';
                    }
                    else
                    {
                        $js_arr[] = trim($js);
                    }
                }
            }
            if (!empty($js_arr))
            {
                $html = preg_replace(array('/\r/', '/\n{2,}/') , '', $html);
                $js_str = implode("\n", $js_arr);
                $body_text = '</body>';
                $html = str_replace($body_text, $js_str.$body_text, $html);
                $response->setBody($html);
            }
        }
    }
}

