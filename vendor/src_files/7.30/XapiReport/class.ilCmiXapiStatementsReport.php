<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * Class ilCmiXapiStatementsReport
 *
 * @author      Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author      Björn Heyser <info@bjoernheyser.de>
 * @author      Stefan Schneider <info@eqsoft.de>
 * @author      Roberto Kalamun Pasini <rp@kalamun.net>
 * edited by MinDefxAPI v7.30
 *
 * @package     Module/CmiXapi
 */
class ilCmiXapiStatementsReport
{
    protected $members; //modif
    /**
     * @var array
     */
    protected $response;
    
    /**
     * @var array
     */
    protected $statements;
    
    /**
     * @var int
     */
    protected $maxCount;
    
    /**
     * @var ilCmiXapiUser[]
     */
    protected $cmixUsersByIdent;

    /**
     * @var string
     */
    protected $userLanguage;
    /**
    * @var ilObjCmiXapi::CONT_TYPE_GENERIC|CONT_TYPE_CMI5
    */
    protected $contentType;
    
    /**
    * @var bool
    */
    protected $isMixedContentType;

    public function __construct(string $responseBody, $obj)
    {
        global $DIC;
        $this->userLanguage = $DIC->user()->getLanguage();
        $responseBody = json_decode($responseBody, true);

        if ($obj instanceof ilObjCmiXapi) {
            $this->contentType = $obj->getContentType();
            $this->isMixedContentType = $obj->isMixedContentType();
        }
        if (count($responseBody)) {
            $this->response = $responseBody; //current($responseBody); //modif
            $this->statements = $this->response['statements'];
            $this->maxCount = count($this->response['statements']);
            $nbStatements = count($this->response['statements']); //modif
            $moreLink = $this->response['more']; //modif
	    ilObjCmiXapi::log()->debug('count = : '.count($responseBody).'| nb statements : '.$nbStatements.'| more : '.$moreLink);
        } else {
            $this->response = '';
            $this->statements = array();
            $this->maxCount = 0;
        }
	$this->members=array();
        foreach (ilCmiXapiUser::getUsersForObject($obj->getId()) as $cmixUser) {
            $this->cmixUsersByIdent[$cmixUser->getUsrIdent()] = $cmixUser;
            array_push($this->members,$cmixUser->getUsrIdent());
        }
    }
    
    public function getMaxCount()
    {
        return $this->maxCount;
    }
    
    public function getStatements()
    {
        return $this->statements;
    }
    
    public function hasStatements()
    {
        return (bool) count($this->statements);
    }
    
    public function getTableData()
    {
        $data = [];
        $this->maxCount=0;
        
        foreach ($this->statements as $index => $statement) {
           if ($this->contentType == ilObjCmiXapi::CONT_TYPE_CMI5){
         		$actor= $statement['actor']['account']['name'];
        	}
           else{
        		$actor=str_replace('mailto:', '', $statement['actor']['mbox']);
        	}
           if (in_array($actor,$this->members)){
            $data[] = [
                'date' => $this->fetchDate($statement),
                'actor' => $this->fetchActor($statement),
                'verb_id' => $this->fetchVerbId($statement),
                'verb_display' => $this->fetchVerbDisplay($statement),
                'object' => $this->fetchObjectName($statement),
                'object_info' => $this->fetchObjectInfo($statement),
                'statement' => json_encode($statement, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            	];
             $this->maxCount=$this->maxCount+1;
            }  
        }
        
        return $data;
    }
    
    protected function fetchDate($statement)
    {
        return $statement['timestamp'];
    }
    
    protected function fetchActor($statement)
    {
        if ($this->isMixedContentType) {
            $ident = str_replace('mailto:', '', $statement['actor']['mbox']);
            if (empty($ident)) {
                $ident = $statement['actor']['account']['name'];
            }
        } elseif ($this->contentType == ilObjCmiXapi::CONT_TYPE_CMI5) {
            $ident = $statement['actor']['account']['name'];
        } else {
            $ident = str_replace('mailto:', '', $statement['actor']['mbox']);
        }
        
        return $this->cmixUsersByIdent[$ident];
    }
    
    protected function fetchVerbId($statement)
    {
        return $statement['verb']['id'];
    }
    
    protected function fetchVerbDisplay($statement)
    {
        try {
            return $statement['verb']['display']['en-US'];
        } catch (Exception $e) {
            return $statement['verb']['id'];
        }
    }
    
    protected function fetchObjectName($statement)
    {
        $ret = urldecode($statement['object']['id']);
        $lang = self::getLanguageEntry($statement['object']['definition']['name'], $this->userLanguage);
        $langEntry = $lang['languageEntry'];
        if ($langEntry != '') {
            $ret = $langEntry;
        }
        return $ret;
    }
    
    protected function fetchObjectInfo($statement)
    {
        try {
        	if (isset($statement['object']['definition']['description']['fr-FR'])){return $statement['object']['definition']['description']['fr-FR'];}
        	elseif (isset($statement['object']['definition']['description']['en-US'])){return $statement['object']['definition']['description']['en-US'];}   
        	else{return "";} 
        } catch (Exception $e) {
            ilObjCmiXapi::log()->debug('debug:' . $e->getMessage());
            return "";
        }
    }

    /**
     * @var array
     *  with multiple language keys like [de-DE] [en-US]
     */
    
    public static function getLanguageEntry($obj, $userLanguage)
    {
        $defaultLanguage = 'en-US';
        $defaultLanguageEntry = '';
        $defaultLanguageExists = false;
        $firstLanguage = '';
        $firstLanguageEntry = '';
        $firstLanguageExists = false;
        $userLanguage = '';
        $userLanguageEntry = '';
        $userLanguageExists = false;
        $language = '';
        $languageEntry = '';
        try {
            foreach ($obj as $k => $v) {
                // save $firstLanguage
                if ($firstLanguage == '') {
                    $f = '/^[a-z]+\-?.*/';
                    if (preg_match($f, $k)) {
                        $firstLanguageExists = true;
                        $firstLanguage = $k;
                        $firstLanguageEntry = $v;
                    }
                }
                // check defaultLanguage
                if ($k == $defaultLanguage) {
                    $defaultLanguageExists = true;
                    $defaultLanguageEntry = $v;
                }
                // check userLanguage
                $p = '/^' . $userLanguage . '\-?./';
                preg_match($p, $k);
                if (preg_match($p, $k)) {
                    $userLanguageExists = true;
                    $userLanguage = $k;
                    $userLanguageEntry = $v;
                }
            }
        } catch (Exception $e) {
        };

        if ($userLanguageExists) {
            $language = $userLanguage;
            $languageEntry = $userLanguageEntry;
        } elseif ($defaultLanguageExists) {
            $language = $userLanguage;
            $languageEntry = $userLanguageEntry;
        } elseif ($firstLanguageExists) {
            $language = $firstLanguage;
            $languageEntry = $firstLanguageEntry;
        }
        return ['language' => $language, 'languageEntry' => $languageEntry];
    }

}
