<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * Class ilCmiXapiHighscoreReport
 *
 * @author      Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author      Björn Heyser <info@bjoernheyser.de>
 * @author      Stefan Schneider <info@eqsoft.de>
 * @author      Roberto Kalamun Pasini <rp@kalamun.net>
 * edited by MinDefxAPI v7.30
 *
 * @package     Module/CmiXapi
 */
class ilCmiXapiHighscoreReport
{
    //public $members;
    /**
     * @var array
     */
    protected $response;

    /**
     * @var array
     */
    private $tableData = [];

    /**
     * @var null|int
     */
    private $userRank = null;
    
    /**
     * @var ilCmiXapiUser[]
     */
    protected $cmixUsersByIdent;
    
    /**
     * @var int
     */
    protected $objId;

    protected $obj;
    /**
     * ilCmiXapiHighscoreReport constructor.
     * @param string $responseBody
     */
    public function __construct(string $responseBody, $obj)
    {
        $this->obj = $obj;
        $this->objId = $obj->getId();
        $responseBody = json_decode($responseBody, true);
    	ilObjCmiXapi::log()->debug('Responsebody :'.$responseBody.'|nb statements'.count($responseBody['statements']));    
        if (count($responseBody)) {
            $this->response = $responseBody;
        } else {
            $this->response = array();
        }
        
        foreach (ilCmiXapiUser::getUsersForObject($this->objId) as $cmixUser) {
            $this->cmixUsersByIdent[$cmixUser->getUsrIdent()] = $cmixUser;
        }
    }

    /**
     * @return bool
     */
    public function initTableData()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */
        $members = array();
        foreach (ilCmiXapiUser::getUsersForObject($this->obj->getId()) as $cmixUser) {
            $this->cmixUsersByIdent[$cmixUser->getUsrIdent()] = $cmixUser;
            array_push($members,$cmixUser->getUsrIdent());}
        $rows = [];

        if ($this->obj instanceof ilObjCmiXapi && $this->obj->isMixedContentType()) {
        ilObjCmiXapi::log()->debug('response '.$this->response.' | nb de lignes '.count($this->response).' | nb statements '.count($this->response['statements']));
            foreach ($this->response['statements'] as $item) {
            	if (isset($item['actor']['account']['name'])){
            		$userIdent = $item['actor']['account']['name'];
            	}
            	else{
            		$userIdent = str_replace('mailto:', '', $item['actor']['mbox']);
            	}
                
                if (in_array($userIdent,$members)){
                	$cmixUser = $this->cmixUsersByIdent[$userIdent];
                	if (isset($item['result']['duration'])){$duration=$item['result']['duration'];}else{$duration = "PT0H0M0S";}
                	$tempRows[] = [
                	'user_ident' => $userIdent,
                    	'user' => '',
                    	'date' => $this->formatRawTimestamp($item['timestamp']),
                    	'duration' => $duration,
                    	'score' => $this->fetchScore($item), //$item['score']['scaled'],
                    	'ilias_user_id' => $cmixUser->getUsrId()
                	];
                }
            }
        } elseif ($this->obj instanceof ilObjCmiXapi && $this->obj->getContentType() == ilObjCmiXapi::CONT_TYPE_CMI5) {
        	ilObjCmiXapi::log()->debug('initTableData de hscoreReport dans elseif nb statements = '.count($this->response['statements']));
            	foreach ($this->response['statements'] as $item) {
            		$userIdent = $item['actor']['account']['name'];
                       if (in_array($userIdent,$members)){
                		$cmixUser = $this->cmixUsersByIdent[$userIdent];
                		if (isset($item['result']['duration'])){$duration=$item['result']['duration'];}else{$duration = "PT0H0M0S";}
                		$tempRows[] = [
                    			'user_ident' => $userIdent,
                    			'user' => '',
                    			'date' => $this->formatRawTimestamp($item['timestamp']),
                    			'duration' => $duration,
                    			'score' => $this->fetchScore($item), //$item['score']['scaled'],
                    			'ilias_user_id' => $cmixUser->getUsrId()
                			];
                	}
            	}
        } else {
                ilObjCmiXapi::log()->debug('nb statements '.count($this->response['statements']));
            	foreach ($this->response['statements'] as $item) {
            		$userIdent = str_replace('mailto:', '', $item['actor']['mbox']);
            		if (in_array($userIdent,$members)){
				$userIdent = str_replace('mailto:', '', $item['actor']['mbox']);
				if (isset($item['result']['duration'])){$duration=$item['result']['duration'];}else{$duration = "PT0H0M0S";}
				$cmixUser = $this->cmixUsersByIdent[$userIdent];
				$tempRows[] = [
                    			'user_ident' => $userIdent,
                    			'user' => '',
                    			'date' => $this->formatRawTimestamp($item['timestamp']),
                    			'duration' => $duration,
                    			'score' => $this->fetchScore($item), //$item['result']['score']['scaled'],
                    			'ilias_user_id' => $cmixUser->getUsrId()
                		];
                	}
            	}
            }

            $rows=$this->arrayFilter($tempRows);
            
        usort($rows, function ($a, $b) {
            return $a['score'] != $b['score'] ? $a['score'] > $b['score'] ? -1 : 1 : 0;
        });

        $i = 0;
        $prevScore = null;
        $retArr = [];
        foreach ($rows as $key => $item) {
            if ($prevScore !== $item['score']) {
                $i++;
            }
            $rows[$key]['rank'] = $i;
            $prevScore = $rows[$key]['score'];
            
            /* instantiate userObj until loginUserRank is unknown */
            if (null === $this->userRank) {
                /* just boolean */
                $userIdent = str_replace('mailto:', '', $rows[$key]['user_ident']);
                $cmixUser = $this->cmixUsersByIdent[$userIdent];
                if ($cmixUser->getUsrId() == $DIC->user()->getId()) {
                    $this->userRank = $key; //$rows[$key]['rank'];
                    $userObj = ilObjectFactory::getInstanceByObjId($cmixUser->getUsrId());
                    $rows[$key]['user'] = $userObj->getFullname();
                }
                $retArr[$key] = $rows[$key];
            } else {
                /* same same */
                $rows[$key]['user_ident'] = false;
                $retArr[$key] = $rows[$key];
            } // EOF if( null === $this->userRank )
        } // EOF foreach ($rows as $key => $item)
        $this->tableData = $retArr;
        return true;
    }
    
    private function fetchScore($statement){
    	if ($statement['result']['score']['scaled']){
    		return  $statement['result']['score']['scaled'];
    		}
    	elseif ($statement['result']['score']['raw']){
    		return $statement['result']['score']['raw']/100;
    		}
    	else {
    		return null;
    		}
    }
    
    private function arrayFilter($tempRows){
    	usort($tempRows, function ($a, $b) {
            return $a['score'] != $b['score'] ? $a['score'] > $b['score'] ? -1 : 1 : 0;
        });
            
            $traite=array();
    	    foreach ($tempRows as $key => $row){
    		$current = $row['user_ident'];
    		$durations = array();
    		$display=array();
    		foreach ($tempRows as $val){
    			if ($current==$val['user_ident'] and in_array($val['user_ident'],$traite)==false){
    				array_push($durations,$val['duration']);	
    				array_push($display,$key);
    			}    			
    			$totalDuration = $this->fetchTotalDuration($durations);
    			$row['duration']=$totalDuration;		
    		}
    		array_push($traite,$row['user_ident']);
	   
	   	if (in_array($key,$display)){
           		$rows[]=['user_ident' => $row['user_ident'],
            			'user'=> $row['user'],
            			'date'=> $row['date'],
            			'duration' => $row['duration'],
            			'score'=> $row['score'],
            			'ilias_user_id' => $row['ilias_user_id']
            			];
            	}
           }
       return $rows;
    }
    
    private function identUser($userIdent)
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */
    
        $cmixUser = $this->cmixUsersByIdent[$userIdent];
        
        if ($cmixUser->getUsrId() == $DIC->user()->getId()) {
            return true;
        }
        return false;
    }
    /*
    private function TotalDuration($allDurations)
    {
    
        $totalDuration = 0;
        
        foreach ($allDurations as $duration) {
            $totalDuration += ilObjSCORM2004LearningModule::_ISODurationToCentisec($duration) / 100;
        }
	
        $hours = floor($totalDuration / 3600);
        $hours = strlen($hours) < 2 ? "0" . $hours : $hours;
        $totalDuration = $hours . ":" . date('i:s', $totalDuration);

        return $totalDuration;
    }*/
    protected function fetchTotalDuration($allDurations)
    {
        $totalDuration = 0;
        if (isset($allDurations)){
        foreach ($allDurations as $duration) {
            $totalDuration += ilObjSCORM2004LearningModule::_ISODurationToCentisec($duration) / 100;
        }
	//$totalDuration = ilObjSCORM2004LearningModule::_ISODurationToCentisec((string)$allDurations) / 100;
        $hours = floor($totalDuration / 3600);
        $hours = strlen($hours) < 2 ? "0" . $hours : $hours;
        $totalDuration = $hours . ":" . date('i:s', $totalDuration);
	}else{$totalDuration = '00:00:00';}
        return $totalDuration;
    }

    private function formatRawTimestamp($rawTimestamp)
    {
        $dateTime = ilCmiXapiDateTime::fromXapiTimestamp($rawTimestamp);
        return ilDatePresentation::formatDate($dateTime);
    }

    public function getTableData()
    {
        return $this->tableData;
    }

    public function getUserRank()
    {
        return $this->userRank;
    }

    public function getResponseDebug()
    {
        /*
        foreach($this->response as $key => $item)
        {
            $user = ilCmiXapiUser::getUserFromIdent(
                ilObjectFactory::getInstanceByRefId($_GET['ref_id']),
                $tableRowData['mbox']
            );

            $this->response[$key]['realname'] = $user->getFullname();
        }
        */
        return '<pre>' . json_encode($this->response, JSON_PRETTY_PRINT) . '</pre>';
    }
}
