<?php

/**
 * Collects IDs of pages that support Admob ads.
 */
class SKMOBILEAPP_CLASS_AdmobPagesEventCollector extends OW_Event
{
    /**
     * Default event name.
     *
     * @type string
     */
    const EVENT_NAME = 'skmobileapp.collect_admob_pages';

    public function __construct($name, array $params = array())
    {
        parent::__construct($name, $params);
        $this->data = [];
    }

    /**
     * Add an Admob page and related data.
     *
     * ```
     * $pageData = [
     *     'adsEnabled' => (bool)          Whether ads are enabled by default or not (required),
     *     'pluginKey'  => (string)        Language plugin key (required),
     *     'langKey'    => (string)        Language key (required),
     *     'regex'      => (string[]|null) Array of regular expressions to mach the in-app path.
     *                                     Provided patterns SHOULD NOT contain delimiters.
     * ]
     * ```
     *
     * @param string $pageId In-app page ID.
     * @param array $pageData Admob page data. Structure is described above.
     */
    public function add($pageId, $pageData)
    {
        $this->data[$pageId] = $pageData;
    }

    /**
     * Add multiple Admob pages with data.
     *
     * @param array $pages A [ pageId => pageData ] mapping. Structure is described in the `add` method documentation.
     * @see SKMOBILEAPP_CLASS_AdmobPagesEventCollector::add()
     */
    public function addMultiple($pages)
    {
        $this->data = array_merge($this->data, $pages);
    }

    /**
     * Remove data by the given page ID.
     *
     * @param string $pageId In-app page ID.
     */
    public function delete($pageId)
    {
        unset($this->data[$pageId]);
    }

    /**
     * Remove data by the given page IDs.
     *
     * @param string[] $keys In-app page IDs.
     */
    public function deleteMultiple($keys)
    {
        foreach ($keys as $key) {
            unset($this->data[$key]);
        }
    }

    /**
     * Replace the collected data entirely with the provided data.
     *
     * @param array $pages A [ pageId => pageData ] mapping. Structure is described in the `add` method documentation.
     */
    public function setData($pages)
    {
        $this->data = $pages;
    }
}