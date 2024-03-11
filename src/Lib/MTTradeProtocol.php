<?php


namespace AleeDhillon\MetaFive\Lib;


class MTTradeProtocol
{
    private $m_connect; // connection to MT5 server
    /**
     * @param MTConnect $connect - connect to MT5 server
     */
    public function __construct($connect)
    {
        $this->m_connect = $connect;
    }

    /**
     * Set balance
     *
     * @param int            $login user login
     * @param MTEnDealAction $type
     * @param double         $balance
     * @param string         $comment
     * @param int            $ticket
     * @param bool           $margin_check
     *
     * @return MTRetCode
     */
    public function TradeBalance($login, $type, $balance, $comment, &$ticket = null,$margin_check=true)
    {
        //--- send request
        $data = array(MTProtocolConsts::WEB_PARAM_LOGIN   => $login,
            MTProtocolConsts::WEB_PARAM_TYPE    => $type,
            MTProtocolConsts::WEB_PARAM_BALANCE => $balance,
            MTProtocolConsts::WEB_PARAM_COMMENT => $comment,
            MTProtocolConsts::WEB_PARAM_CHECK_MARGIN => $margin_check?"1":"0",
        );
        if(!$this->m_connect->Send(MTProtocolConsts::WEB_CMD_TRADE_BALANCE, $data))
        {
            if(MTLogger::getIsWriteLog()) MTLogger::write(MTLoggerType::ERROR, 'send trade balance failed');
            return MTRetCode::MT_RET_ERR_NETWORK;
        }
        //--- get answer
        if(($answer = $this->m_connect->Read()) == null)
        {
            if(MTLogger::getIsWriteLog()) MTLogger::write(MTLoggerType::ERROR, 'answer trade balance is empty');
            return MTRetCode::MT_RET_ERR_NETWORK;
        }

        //--- parse answer
        $trade_answer = null;
        //---
        if(($error_code = $this->Parse($answer, $trade_answer)) != MTRetCode::MT_RET_OK)
        {

            if(MTLogger::getIsWriteLog()) MTLogger::write(MTLoggerType::ERROR, 'parse trade balance failed: [' . $error_code . ']' . MTRetCode::GetError($error_code));
            return $error_code;
        }

        //---
        $ticket = $trade_answer->Ticket;
        //---
        return MTRetCode::MT_RET_OK;
    }

    /**
     * check answer from MetaTrader 5 server
     *
     * @param string         $answer - answer from server
     * @param  MTTradeAnswer $trade_answer
     *
     * @return MTRetCode
     */
    private function Parse(&$answer, &$trade_answer)
    {
        $pattern = '/TRADE_BALANCE\|RETCODE=(\d+)\s+Done\|TICKET=(\d+)\|\r\n/';

    // Attempt to match the response format using regular expression
    if (preg_match($pattern, $answer, $matches)) {
        if (count($matches) === 3) {
            // Ensure object is created before accessing properties
            $trade_answer = new MTTradeAnswer();

            // Extract data from matched groups (assuming correct format)
            $trade_answer->RetCode = $matches[1];
            $trade_answer->Ticket = $matches[2];

            // ... rest of your logic for handling successful parsing
            return MTRetCode::MT_RET_OK;
        } else {
            // Handle parsing error (format mismatch - number of matches)
            return MTRetCode::MT_RET_ERR_DATA; // Or a specific error code for format mismatch
        }
    } else {
        // Handle parsing error (format mismatch - no match found)
        return MTRetCode::MT_RET_ERR_DATA; // Or a specific error code for format mismatch
    }

    // This line is unreachable due to the return statements above
    // but can be kept for clarity
    return MTRetCode::MT_RET_OK;
    }
}

/**
 * get trade answer
 */
class MTTradeAnswer
{
    public $RetCode = '0';
    public $Ticket = 0;
}
