<?php

namespace lx;

class RecursiveTree
{
    private string $key;
    private array $parents = [];
    private array $children = [];
    private ?DataObject $common = null;
    private DataObject $data;

    public static function create(?RecursiveTree $tree = null, ?string $key = null): RecursiveTree
    {
        $self = self::createBlank();

        if ($tree) {
            $self->common = $tree->common;
            $self->parents[] = $tree->key;
        } else {
            $self->common = new RecursiveTreeCommon();
        }

        $self->key = $key ?: $self->genKey();

        if (array_key_exists($self->key, $self->common->map)) {
            throw new \Exception('RecursiveTree: key already exists');
        }

//        $self->common->nodes[] = $self;
        $self->common->map[$self->key] = $self;

        return $self;
    }

    public static function createBlank(): RecursiveTree
    {
        return new RecursiveTree();
    }

    private function __construct()
    {
        $this->data = new DataObject();
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getNode(string $key): ?RecursiveTree
    {
        return $this->common->map[$key] ?? null;
    }

    public function count(): int
    {
        return count($this->children);
    }

    public function getNth(int $i): ?RecursiveTree
    {
        return isset($this->common) ? $this->common->map[$this->children[$i]] : null;
    }

    private function genKey(): string
    {
        return 'r' . $this->common->keyCounter++;
    }

    /**
     * @param RecursiveTree|string $keyOrNode
     */
    public function add($keyOrNode = null): RecursiveTree
    {
        if ($keyOrNode instanceof RecursiveTree) {
            return $this->addNode($keyOrNode);
        }
        return $this->addNew($keyOrNode);
    }

    public function addNode(RecursiveTree $node): RecursiveTree
    {
        if (!in_array($node, $this->common->map, true)) {
            throw new Exception('RecursiveTree: the node from different tree');
        }

        if (array_key_exists($node->key, $this->children)) {
            return $node;
        }
        $this->children[] = $node->key;
        $node->parents[] = $this->key;
        return $node;
    }

    public function addNew(?string $key = null): RecursiveTree
    {
        $node = self::create($this, $key);
        $this->children[] = $node->key;
        return $node;
    }

    public function setData(array $data): void
    {
        $this->data->setProperties($data);
    }

    public function del(): void
    {
        // TODO: Implement del() method.
    }

    public function toArray(): array
    {
        $map = [];
        /** @var RecursiveTree $node */
        foreach ($this->common->map as $key => $node) {
            $map[$key] = [
                'parents' => $node->parents,
                'children' => $node->children,
                'data' => $node->data->toArray(),
            ];
        }

        return [
            'title' => $this->key,
            'keyCounter' => $this->common->keyCounter,
            'map' => $map,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}

/**
 * @property int $keyCounter
 * @property array $map
 */
class RecursiveTreeCommon extends DataObject
{
    public function __construct()
    {
        parent::__construct([
            'keyCounter' => 0,
            'map' => [],
        ]);
    }
}
