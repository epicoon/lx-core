<?php

namespace lx;

/**
 * @property-read HtmlTemplateProviderInterface $templateProvider
 */
class HtmlRenderer implements HtmlRendererInterface, ObjectInterface
{
    use ObjectTrait;

    const TEMPLATE_TYPE_REGULAR = 200;
    const TEMPLATE_TYPE_REQUEST_ERROR = 400;
    const TEMPLATE_TYPE_SERVER_ERROR = 500;
    const TEMPLATE_TYPE_UNAUTHORIZED = 401;
    const TEMPLATE_TYPE_FORBIDDEN = 403;

    private ?HtmlTemplateProviderInterface $customTemplateProvider = null;
    /** @var string|int|null */
    private $templateType = null;
    private ?string $templatePath = null;
    private ?FileInterface $templateFile = null;
    private array $params = [];

    public static function isSingleton(): bool
    {
        return true;
    }

    public static function getDependenciesConfig(): array
    {
        return [
            'templateProvider' => [
                'class' => HtmlTemplateProviderInterface::class,
                'lazy' => true,
            ],
        ];
    }

    public function setTemplateProvider(HtmlTemplateProviderInterface $provider): HtmlRenderer
    {
        $this->customTemplateProvider = $provider;
        return $this;
    }

    /**
     * @param string|int $type
     */
    public function setTemplateType($type): HtmlRenderer
    {
        $this->templateType = $type;
        return $this;
    }

    public function setTemplatePath(string $path): HtmlRenderer
    {
        $this->templatePath = $path;
        return $this;
    }

    public function setTemplateFile(FileInterface $file): HtmlRenderer
    {
        $this->templateFile = $file;
        return $this;
    }

    public function setParams(array $params): HtmlRenderer
    {
        $this->params = $params;
        return $this;
    }

    public function getTemplateProvider(): HtmlTemplateProviderInterface
    {
        return $this->customTemplateProvider ?? $this->templateProvider;
    }

    public function getTemplatePath(): ?string
    {
        if ($this->templateFile) {
            $this->templatePath = $this->templateFile->getPath();
        }
        
        if ($this->templatePath) {
            return $this->templatePath;
        }
        
        if ($this->templateType) {
            return $this->getTemplateProvider()->getTemplatePath($this->templateType);
        }
        
        return null;
    }

    public function render(): string
    {
        $templatePath = $this->getTemplatePath();
        
        extract($this->params);
        ob_start();
        require($templatePath);

        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }
}
