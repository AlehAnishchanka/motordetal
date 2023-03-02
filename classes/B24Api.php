<?php

namespace  report;
use Helper;

class B24Api
{

    CONST WEB_HOOK = 'https://motordetal.bitrix24.ru/rest/2944/3tux3pkmwzqpz1z1/';

    CONST AR_METHODS = [
        'GET_LIST_STAGES'=>'crm.status.list',
        'GET_LIST_VACANCY' => 'crm.item.list', // требуется префикс например crm.item.list?entityTypeId=135
        'GET_LIST_USERS' => 'user.get'
    ];

    protected function SendRequest($method, $arParams)
    {
        $queryUrl = B24Api::WEB_HOOK.$method;
        $queryData = http_build_query($arParams);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $queryUrl,
            CURLOPT_POSTFIELDS => $queryData,
        ));
        $result = curl_exec($curl);
        curl_close($curl);
        $result = json_decode($result);
        return $result;
    }

    public function getItemsQuantity($method, $filter='', $preset='')
    {

        $result = $this->SendRequest($method . "?$preset" . http_build_query(['filter' => $filter,'select'=>[]]), []);
        return $result->total;
    }

    public function getArCmdItem($method, $arFilter, $arSelect, $order=["ID"=> "asc"], $preset='')
    {
        $cmdArr = [];
        $preset = $preset ? "$preset&" : $preset;
        $itemsCount = $this->getItemsQuantity($method, $arFilter, $preset);
        $countPages = intdiv($itemsCount,50);
        $countPages = $itemsCount%50>0 ? $countPages + 1 : $countPages;
        for($i=0; $i<$countPages; $i++) {
            $cmdArr[$method . '_' . $i] =
                $method . "?$preset" .  http_build_query(
                    [
                        'order' => $order,
                        'start' => $i*50,
                        'filter' => $arFilter,
                        'select' => $arSelect
                    ]);
        }
        return $cmdArr;
    }

    public function getArBatchResult($methodApi, $arFilter=[], $arSelect=["*", "UF_*"], $order=["ID"=> "asc"], $preset='')
    {
        $method = 'batch';
        $array = [
            'halt' => 0,
            'cmd' => $this->getArCmdItem($methodApi, $arFilter, $arSelect, $order, $preset)
        ];
        $arCountResult =  \Helper::object_to_array($this->SendRequest($method, $array)->result->result);
        return $arCountResult;
    }

    public function getArSimpleResult($methodApi, $param=[], $arFilter=[])
    {
        if($param) {
            $methodApi .= '?';
            foreach($param as $key=>$val) {
                $methodApi .= "$key=$val&";
            }
            $methodApi = substr($methodApi, 0 , strlen($methodApi)-1);
        }
        $arCountResult =  \Helper::object_to_array($this->SendRequest($methodApi, $arFilter)->result);
        return $arCountResult;
    }

    public function getArBatchParamDependResult($methodApi, $paramName, $arParamValues)
    {
        $arCmd = [];
        foreach($arParamValues as $paramValue) {
            $arCmd[$paramValue] = "$methodApi?$paramName=$paramValue";
        }
        $method = 'batch';
        $array = [
            'halt' => 0,
            'cmd' => $arCmd
        ];
        $arCountResult =  \Helper::object_to_array($this->SendRequest($method, $array)->result->result);
        return $arCountResult;
    }
}