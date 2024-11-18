<?php

namespace Typoheads\Formhandler\Domain\Model;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
/**
 * Demand object for log data
 */
class Demand
{
    /**
     * @var int
     */
    protected $pid = 0;

    /**
     * @var string
     */
    protected $ip = '';

    /**
     * Calculated start timestamp
     *
     * @var int
     */
    protected $startTimestamp = 0;

    /**
     * Calculated end timestamp
     *
     * @var int
     */
    protected $endTimestamp = 0;

    /**
     * Manual date start
     * @var \DateTime|null
     */
    protected $manualDateStart;

    /**
     * Manual date stop
     * @var \DateTime|null
     */
    protected $manualDateStop;

    protected $page = 1;

    protected $limit = 10;

    /**
     * @return string|null
     */
    public function getIp(): ?string
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     */
    public function setIp(?string $ip = null): void
    {
        $this->ip = $ip;
    }

    /**
     * @return int
     */
    public function getPid(): int
    {
        return $this->pid;
    }

    /**
     * @param int $pid
     */
    public function setPid(int $pid): void
    {
        $this->pid = $pid;
    }

    /**
     * @return int
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * @param int $page
     */
    public function setPage(int $page): void
    {
        $this->page = $page;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     */
    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }

    /**
     * Offset for the current set of records
     */
    public function getOffset(): int
    {
        return ($this->page - 1) * $this->limit;
    }

    public function getStartTimestamp(): int
    {
        return $this->startTimestamp;
    }

    public function setStartTimestamp(int $startTimestamp): void
    {
        $this->startTimestamp = $startTimestamp;
    }

    public function getEndTimestamp(): int
    {
        return $this->endTimestamp;
    }

    public function setEndTimestamp(int $endTimestamp): void
    {
        $this->endTimestamp = $endTimestamp;
    }

    public function getParameters(): array
    {
        $parameters = [];
        if ($this->getIp()) {
            $parameters['ip'] = $this->getIp();
        }
        if ($this->getManualDateStop()) {
            $parameters['manualDateStop'] = $this->getManualDateStop()->format('c');
        }
        if ($this->getManualDateStart()) {
            $parameters['manualDateStart'] = $this->getManualDateStart()->format('c');
        }
        if ($this->getPid()) {
            $parameters['pid'] = $this->getPid();
        }
        if ($this->getLimit()) {
            $parameters['limit'] = $this->getLimit();
        }
        return $parameters;
    }

    /**
     * Set manual date start
     *
     * @param \DateTime $manualDateStart
     */
    public function setManualDateStart(?\DateTime $manualDateStart = null): void
    {
        $this->manualDateStart = $manualDateStart;
    }

    /**
     * Get manual date start
     *
     * @return \DateTime|null
     */
    public function getManualDateStart()
    {
        return $this->manualDateStart;
    }

    /**
     * Set manual date stop
     *
     * @param \DateTime $manualDateStop
     */
    public function setManualDateStop(?\DateTime $manualDateStop = null): void
    {
        $this->manualDateStop = $manualDateStop;
    }

    /**
     * Get manual date stop
     *
     * @return \DateTime|null
     */
    public function getManualDateStop()
    {
        return $this->manualDateStop;
    }
}
