<?php

declare(strict_types=1);

namespace TheCodingMachine\GraphQLite\Reflection;

use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\Context;
use phpDocumentor\Reflection\Types\ContextFactory;
use Psr\SimpleCache\CacheInterface;
use ReflectionMethod;
use function filemtime;
use function md5;

/**
 * Creates DocBlocks and puts these in cache.
 */
class CachedDocBlockFactory
{
    /** @var CacheInterface */
    private $cache;
    /** @var DocBlockFactory|null */
    private $docBlockFactory;
    /** @var array<string, DocBlock> */
    private $docBlockArrayCache = [];
    /** @var array<string, Context> */
    private $contextArrayCache = [];
    /** @var ContextFactory */
    private $contextFactory;

    /**
     * @param CacheInterface $cache The cache we fetch data from. Note this is a SAFE cache. It does not need to be purged.
     */
    public function __construct(CacheInterface $cache, ?DocBlockFactory $docBlockFactory = null)
    {
        $this->cache           = $cache;
        $this->docBlockFactory = $docBlockFactory ?: DocBlockFactory::createInstance();
        $this->contextFactory  = new ContextFactory();
    }

    /**
     * Fetches a DocBlock object from a ReflectionMethod
     */
    public function getDocBlock(ReflectionMethod $refMethod) : DocBlock
    {
        $key = 'docblock_' . md5($refMethod->getDeclaringClass()->getName() . '::' . $refMethod->getName());
        if (isset($this->docBlockArrayCache[$key])) {
            return $this->docBlockArrayCache[$key];
        }

        $cacheItem = $this->cache->get($key);
        if ($cacheItem !== null) {
            [
                'time' => $time,
                'docblock' => $docBlock,
            ] = $cacheItem;

            if (filemtime($refMethod->getFileName()) === $time) {
                $this->docBlockArrayCache[$key] = $docBlock;

                return $docBlock;
            }
        }

        $docBlock = $this->doGetDocBlock($refMethod);

        $this->cache->set($key, [
            'time' => filemtime($refMethod->getFileName()),
            'docblock' => $docBlock,
        ]);
        $this->docBlockArrayCache[$key] = $docBlock;

        return $docBlock;
    }

    private function doGetDocBlock(ReflectionMethod $refMethod) : DocBlock
    {
        $docComment = $refMethod->getDocComment() ?: '/** */';

        $refClass     = $refMethod->getDeclaringClass();
        $refClassName = $refClass->getName();

        if (! isset($this->contextArrayCache[$refClassName])) {
            $this->contextArrayCache[$refClassName] = $this->contextFactory->createFromReflector($refMethod);
        }

        return $this->docBlockFactory->create($docComment, $this->contextArrayCache[$refClassName]);
    }
}
