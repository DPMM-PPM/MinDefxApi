<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * Class ilCmiXapiAbstractReportLinkBuilder
 *
 * @author      Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author      Björn Heyser <info@bjoernheyser.de>
 * @author      Stefan Schneider <info@eqsoft.de>
 * @author      Roberto Kalamun Pasini <rp@kalamun.net>
 * edited by MinDefxAPI v7.30
 *
 * @package     Module/CmiXapi
 */
abstract class ilCmiXapiAbstractReportLinkBuilder
{
    /**
     * @var int
     */
    protected $objId;
    
    /**
     * @var string
     */
    protected $aggregateEndPoint;

    /**
     * @var ilCmiXapiStatementsReportFilter
     */
    protected $filter;

    protected $obj;
    
    /**
     * ilCmiXapiAbstractReportLinkBuilder constructor.
     * @param $obj
     * @param $userIdentMode
     * @param $aggregateEndPoint
     * @param ilCmiXapiStatementsReportFilter $filter
     */
    public function __construct(
        $obj,
        $aggregateEndPoint,
        ilCmiXapiStatementsReportFilter $filter
    ) {
        $this->obj = $obj;
        $this->objId = $obj->getID();
        $this->aggregateEndPoint = $aggregateEndPoint;
        $this->filter = $filter;
    }
    
    /**
     * @return string
     */
    public function getUrl()
    {
        $url = $this->aggregateEndPoint;
    ilObjCmiXapi::log()->debug('url : '.$url);
    ilObjCmiXapi::log()->debug('params : '.$this->appendRequestParameters($url));
        $url = $this->appendRequestParameters($url);
        return $url;
    }
    
    /**
     * @param string $link
     * @return string
     */
    protected function appendRequestParameters($url)
    {
        $url = ilUtil::appendUrlParameterString($url, $this->buildPipelineParameter());
        
        return $url;
    }
    
    /**
     * @return string
     */
    protected function buildPipelineParameter()
    {
        return $this->buildPipeline()[0];
    }
    
    /**
     * @return array
     */
    abstract protected function buildPipeline() : array;
    
    /**
     * @return int
     */
    public function getObjId()
    {
        return $this->objId;
    }
    
    /**
     * @return string
     */
    public function getAggregateEndPoint()
    {
        return $this->aggregateEndPoint;
    }
    

    /**
     * @return ilObjCmiXapi
     */
    public function getObj()
    {
        return $this->obj;
    }
}
