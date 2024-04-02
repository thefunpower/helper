<?php


if(!function_exists("xml2array")) {
    function xml2array($xml_content)
    {
        $doc = new \DOMDocument();
        $doc->loadXML($xml_content);
        $root = $doc->documentElement;
        $output = (array) _xml2array_node($root);
        $output['@root'] = $root->tagName;
        return $output ?? [];
    }

    function _xml2array_node($node)
    {
        $output = [];
        switch ($node->nodeType) {
            case 4:
            case 3:
                $output = trim($node->textContent);
                break;
            case 1:
                for ($i = 0, $m = $node->childNodes->length; $i < $m; $i++) {
                    $child = $node->childNodes->item($i);
                    $v =  _xml2array_node($child);
                    if (isset($child->tagName)) {
                        $t = $child->tagName;
                        if (!isset($output[$t])) {
                            $output[$t] = [];
                        }
                        if (is_array($v) && empty($v)) {
                            $v = '';
                        }
                        $output[$t][] = $v;
                    } elseif ($v || $v === '0') {
                        $output = (string) $v;
                    }
                }
                if ($node->attributes->length && !is_array($output)) {
                    $output = ['@content' => $output];
                }
                if (is_array($output)) {
                    if ($node->attributes->length) {
                        $attr = [];
                        foreach ($node->attributes as $name => $node) {
                            $attr[$name] = (string) $node->value;
                        }
                        $output['@attributes'] = $attr;
                    }
                    foreach ($output as $t => $v) {
                        if ($t !== '@attributes' && is_array($v) && count($v) === 1) {
                            $output[$t] = $v[0];
                        }
                    }
                }
                break;
        }
        return $output;
    }

}

if(!function_exists("array2xml")) {
    function array2xml($arr, $root = '')
    {
        return Spatie\ArrayToXml\ArrayToXml::convert($arr, $root);
    }
}