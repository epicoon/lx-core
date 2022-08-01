<?php

namespace lx;

class HtmlTemplateProvider implements HtmlTemplateProviderInterface
{
    /**
     * @param string|int $templateType
     */
    public function getTemplatePath($templateType): string
    {
        $path = \lx::$conductor->stdResponses . '/' . $templateType . '.php';

        if (!file_exists($path)) {
            $path = \lx::$conductor->stdResponses . '/' . $templateType . '.html';
        }

        if (!file_exists($path)) {
            \lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
                '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
                'msg' => "Template '$templateType' not found'",
            ]);
            $path = \lx::$conductor->stdResponses . '/500.php';
        }
        
        return $path;
    }
}
