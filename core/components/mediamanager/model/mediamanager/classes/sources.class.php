<?php

class MediaManagerSourcesHelper
{
    /**
     * The mediaManager object.
     */
    private $mediaManager = null;

    /**
     * @var int
     */
    private $defaultSource = 0;

    /**
     * @var int
     */
    private $currentSource = 0;

    /**
     * @var int
     */
    private $userSource = 0;

    /**
     * MediaManagerSourcesHelper constructor.
     *
     * @param MediaManager $mediaManager
     */
    public function __construct(MediaManager $mediaManager)
    {
        $this->mediaManager = $mediaManager;

        $this->setUserSource();
        $this->setDefaultSource();
        $this->setCurrentSource();
        $this->hasPermission();
    }

    /**
     * Set default media source.
     *
     * @return int
     */
    private function setDefaultSource()
    {
        $source = $this->mediaManager->modx->getObject('modMediaSource', [
            'id' => $this->mediaManager->modx->getOption('mediamanager.default_media_source')
        ]);

        if ($source) {
            $this->defaultSource = (int) $source->get('id');
        } else if ($this->getUserSource()) {
            $this->defaultSource = $this->getUserSource();
        }

        return $this->defaultSource;
    }

    /**
     * Set current media source.
     *
     * @return int
     */
    private function setCurrentSource()
    {
        $sourceId = (int) $_REQUEST['source'];

        if (!$sourceId && isset($_SESSION['mediamanager']['source'])) {
            return $this->currentSource = $_SESSION['mediamanager']['source'];
        }

        if ($sourceId) {
            $source = $this->mediaManager->modx->getObject('modMediaSource', [
                'id' => $sourceId
            ]);

            if ($source) {
                $_SESSION['mediamanager']['source'] = $sourceId;
                return $this->currentSource = $sourceId;
            }

            $this->mediaManager->modx->sendError('fatal');
        }

        return $this->currentSource = $this->defaultSource;
    }

    /**
     * Set user media source.
     *
     * @return int
     */
    private function setUserSource()
    {
        $userSettings = $this->mediaManager->modx->user->getSettings();

        return $this->userSource = (int) $userSettings['media_sources_id'];
    }

    /**
     * Check if user is allowed to view current source.
     */
    private function hasPermission()
    {
        if (
            $this->userSource
            && $this->userSource !== $this->currentSource
            && $this->defaultSource !== $this->currentSource
        ) {
            $this->mediaManager->modx->sendError('fatal');
        }
    }

    /**
     * Get default media source.
     *
     * @return int
     */
    public function getDefaultSource()
    {
        return $this->defaultSource;
    }

    /**
     * Get current media source.
     *
     * @return int
     */
    public function getCurrentSource()
    {
        return $this->currentSource;
    }

    /**
     * Get user media source.
     *
     * @return int
     */
    public function getUserSource()
    {
        return $this->userSource;
    }

    /**
     * Get media sources.
     * @param boolean $count
     * @return mixed
     */
    public function getList($count = false)
    {
        $mediaSources = $this->mediaManager->modx->getIterator('modMediaSource');

        $sources = [];
        foreach ($mediaSources as $source) {
            $properties = $source->get('properties');

            if ($properties['mediamanagerSource']['value']) {
                if ($this->getUserSource() && $this->getUserSource() !== $source->get('id')) {
                    continue;
                }

                $rank = (float) ($properties['rank']['value'] ?: 1) . '.' . $source->get('id');
                $sources[$rank] = [
                    'id'               => $source->get('id'),
                    'name'             => $source->get('name'),
                    'basePath'         => $properties['basePath']['value'] ?: '',
                    'basePathRelative' => $properties['basePathRelative']['value'],
                    'baseUrl'          => $properties['baseUrl']['value'] ?: '',
                    'baseUrlRelative'  => $properties['baseUrlRelative']['value'],
                    'allowedFileTypes' => $properties['allowedFileTypes']['value'] ?: ''
                ];
            }
        }

        if ($count) {
            return count($sources);
        }

        ksort($sources);

        return $sources;
    }

    /**
     * Get media source html.
     *
     * @return string
     */
    public function getListHtml()
    {
        $html = '';
        $sources = $this->getList();

        foreach ($sources as $source) {
            $source['selected'] = 0;
            if ($source['id'] === $this->getCurrentSource()) {
                $source['selected'] = 1;
            }

            $html .= $this->mediaManager->getChunk('sources/source', $source);
        }

        if (empty($html)) {
            $html = $this->mediaManager->modx->lexicon('mediamanager.sources.error.no_sources_found');
        }

        return $html;
    }

    /**
     * Get media source by id.
     *
     * @param int $sourceId
     *
     * @return bool|array
     */
    public function getSource($sourceId)
    {
        $source = $this->mediaManager->modx->getObject('modMediaSource', [
            'id' => $sourceId
        ]);

        if (!$source) {
            return false;
        }

        $properties = $source->get('properties');

        if (!$properties['mediamanagerSource']['value']) {
            return false;
        }

        $source = [
            'id'               => $source->get('id'),
            'name'             => $source->get('name'),
            'basePath'         => $properties['basePath']['value'] ?: '',
            'basePathRelative' => $properties['basePathRelative']['value'],
            'baseUrl'          => $properties['baseUrl']['value'] ?: '',
            'baseUrlRelative'  => $properties['baseUrlRelative']['value'],
            'allowedFileTypes' => isset($properties['allowedFileTypes']) ? $properties['allowedFileTypes']['value'] : ''
        ];

        return $source;
    }
}
