<?php

namespace _PhpScoper5ea00cc67502b\Mollie\Api\Resources;

class MandateCollection extends \_PhpScoper5ea00cc67502b\Mollie\Api\Resources\CursorCollection
{
    /**
     * @return string
     */
    public function getCollectionResourceName()
    {
        return "mandates";
    }
    /**
     * @return BaseResource
     */
    protected function createResourceObject()
    {
        return new \_PhpScoper5ea00cc67502b\Mollie\Api\Resources\Mandate($this->client);
    }
    /**
     * @param string $status
     * @return array|\Mollie\Api\Resources\MandateCollection
     */
    public function whereStatus($status)
    {
        $collection = new self($this->client, $this->count, $this->_links);
        foreach ($this as $item) {
            if ($item->status === $status) {
                $collection[] = $item;
            }
        }
        return $collection;
    }
}
