<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * Class ilCmiXapiStatmentsAggregateLinkBuilder
 *
 * @author      Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author      Björn Heyser <info@bjoernheyser.de>
 * @author      Stefan Schneider <info@eqsoft.de>
 * @author      Roberto Kalamun Pasini <rp@kalamun.net>
 * edited by MinDefxAPI v7.30
 *
 * @package     Module/CmiXapi
 */
class ilCmiXapiStatementsReportLinkBuilder extends ilCmiXapiAbstractReportLinkBuilder
{
    /**
     * @return array
     */
    protected function buildPipeline() : array
    {
   // require_once('class.ilCmiXapiStatementsReportFilter.php');
        $pipeline = array();
        $obj = $this->getObj();

        $params='activity='.$obj->getActivityId();
        if($this->filter->getVerb()!=''){
        	$params.='&verb='.$this->filter->getVerb();
        }
        
        
 //       if ($this->filter->getStartDate() || $this->filter->getEndDate()){
        if (true){
        	if ($this->filter->getStartDate()){
        		$tempDate=date_create(str_replace("3600","",$this->filter->getStartDate()->toXapiTimestamp()),timezone_open($this->filter->getStartDate()->getTimeZoneIdentifier()));
        		$tempDate= date_timezone_set($tempDate, timezone_open('UTC'));
        		$params.= '&since='.date_format($tempDate, 'Y-m-d\TH:i:s');
        	}
 
        	if ($this->filter->getEndDate()){
        		$tempDate=date_create(str_replace("3600","",$this->filter->getEndDate()->toXapiTimestamp()),timezone_open($this->filter->getEndDate()->getTimeZoneIdentifier()));
        		$tempDate= date_timezone_set($tempDate, timezone_open('UTC'));
        		$params.= '&until='.date_format($tempDate, 'Y-m-d\TH:i:s');
        	}
        }
        
        if ($this->filter->getActor()){
        
        	if($obj->getContentType() == ilObjCmiXapi::CONT_TYPE_CMI5){
        	  	$params.='&agent={"account":{"homePage":"http://'.str_replace('www.', '', $_SERVER['HTTP_HOST']).'","name":"'.$this->filter->getActor()->getUsrIdent().'"}}';
        	}
        	else{
        		$params.='&agent={"mbox":"mailto:'.$this->filter->getActor()->getUsrIdent().'"}';
        	}
        }
        if ($this->orderingField()=='dateAsc'){$params.='&ascending=true';}
        
        
        $pipeline=array($params.'&related_activities='.$this->buildRelatedActivities().'&limit=0');
        return $pipeline;
    }
    
    // modification mgd
    protected function buildActivityId()
    {
    	$obj = $this->getObj();
    	return $obj->getActivityId();
    }
    
    protected function buildRelatedActivities()
    {
    	return 'true';
    }
    
    public function orderingField(){
       switch ($this->filter->getOrderField()) {
            case 'object': // definition/description are displayed in the Table if not empty => sorting not alphabetical on displayed fields
                $column = 'objet';
                ilUtil::sendInfo("Le tri par $column n'est pas disponible");
                break;
                
            case 'verb':
                $column = 'verbe';
                ilUtil::sendInfo("Le tri par $column n'est pas disponible");
                break;
                
            case 'actor':
                $column = 'utilisateur';
                ilUtil::sendInfo("Le tri par $column n'est pas disponible");
                break;
                
            case 'date':
            	if ($this->filter->getOrderDirection()=='asc'){
            	    	$column='dateAsc';
            	    	}
            	else {$column='dateDesc';}
            	break;
            default:
                $column = 'dateDesc';
                break;
        }
        
        return $column;
    }
    // fin modif
    
    protected function buildLimitStage()
    {
    }
    
    protected function buildFilterStage()
    {
    }
    
    protected function buildOrderingStage()
    {
    }
}
