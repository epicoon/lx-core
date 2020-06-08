<?php

namespace lx;

/**
 * Class Renderer
 * @package lx
 */
class Renderer implements RendererInterface
{
    use ObjectTrait;

    /**
     * @return bool
     */
    public static function isSingleton()
    {
        return true;
    }

    /**
     * @param string $template
     * @param array $params
     * @return string
     */
    public function render($template, $params = [])
    {
        if (!file_exists($template)) {
            $template = \lx::$conductor->stdResponses . '/' . $template;
        }

        if (!file_exists($template)) {
            \lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
                '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
                'msg' => "Template '$template' not found'",
            ]);
            return '';
        }

        extract($params);
        ob_start();
        require($template);

        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }
}
