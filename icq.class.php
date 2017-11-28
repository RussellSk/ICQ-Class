<?
///////////////////////////////////////////////////////////////////////////////
//
// ICQ (OSCAR) Protocol implementation
// coder: Russell Sk.
// version: 1.0
// date: 07.03.2006
//---------------------------------[php]CLASS-----------------------------------

class ICQ {
    
    function connect($_uin, $_pass)
    {
        $this->sock = fsockopen("login.icq.com",5190);
        if($this->sock){ 
            echo("Failed to connect"); 
        }
        $this->PackBody = $this->ReadPack();
        $this->Sequence = rand(1, 30000);
        $hash_pass = "";
        $arr_roast = array(0xF3,0x26,0x81,0xC4,0x39,0x86,0xDB,0x92,0x71,0xA3,0xB9,0xE6,0x53,0x7A,0x95,0x7C);
        for($i=0; $i < strlen($_pass); $hash_pass .= chr($arr_roast[$i]^ord($_pass[$i++]))); 
        $this->PackBody .= pack("nna*",1,strlen($_uin),$_uin);
        $this->PackBody .= pack("nna*",2,strlen($hash_pass),$hash_pass);
        $this->PackBody .= pack("nna*",3,strlen("Ukraine"),"UAC0D3");
        $this->PackBody .= pack("nnn",22,2,266);
        $this->PackBody .= pack("nnn",23,2,20);
        $this->PackBody .= pack("nnn",24,2,34);
        $this->PackBody .= pack("nnn",25,2,0);
        $this->PackBody .= pack("nnn",26,2,2321);
        $this->PackBody .= pack("nnN",20,4,1085);
        $this->PackBody .= pack("nna*",15,strlen('ru'),'ru');
        $this->PackBody .= pack("nna*",14,strlen('ru'),'ru');
        $this->Flap['chanel2'] = 1;
        fwrite($this->sock,$this->GetFlap()); // Send packet to server
        $this->PackBody = $this->ReadPack();
        $this->info = array();
        echo("<br />Cookie:".$this->PackBody."<br />");
        while($this->PackBody != '') 
        {
            $pars = unpack('n2',substr($this->PackBody, 0, 4));
            $this->TLV_id = $pars[1];
            $this->Size = $pars[2];
            $this->info['Data'] = substr($this->PackBody, 4, $this->Size);
            switch($this->TLV_id)
            {
                case 6: $this->info['cookie'] = $this->info['Data'];
                 break;
                case 5: $this->info['adres'] = $this->info['Data'];
                 break;
            }
            $this->PackBody = substr($this->PackBody,($this->Size+4));
        }
        $this->icqBye();
        if(isset($this->info['adres']))
        {
            $addr = explode(':',$this->info['adres']);
            $this->sock = fsockopen($addr[0],$addr[1]);
        }
        $this->PackBody  = $this->ReadPack();
        $this->PackBody .= pack('nna*',6, strlen($this->info['cookie']), $this->info['cookie']); // Pack: Hello + Cookie
        fwrite($this->sock, $this->GetFlap());
        $this->PackBody = $this->ReadPack();
        $this->Request_id++;
        $this->PackBody = pack('nnnN',1,2,0,$this->Request_id);
        $this->PackBody .= pack('n*',1,3,272,650);
        $this->PackBody .= pack('n*',2,1,272,650);
        $this->PackBody .= pack('n*',3,1,272,650);
        $this->PackBody .= pack('n*',21,1,272,650);
        $this->PackBody .= pack('n*',4,1,272,650);
        $this->PackBody .= pack('n*',6,1,272,650);
        $this->PackBody .= pack('n*',9,1,272,650);
        $this->PackBody .= pack('n*',10,1,272,650);
        fwrite($this->sock,$this->GetFlap());
        return true;
    }
    
    function SendMsg($uin, $message)
    {
        $this->Request_id++;
        $cookie = microtime();
        $this->Flap['chanel2'] = 2;
        $DeForCapa = pack('H*','094613494C7F11D18222444553540000');
        $TLV_Data = pack('nd',0,$cookie).$DeForCapa;
        $TLV_Data .=pack('nnn',10,2,1);
        $TLV_Data .=pack('nn', 15, 0);
        $TLV_Data .=pack('nnvvddnVn',10001,strlen($message)+62,27,8,0,0,0,3,$this->Request_id);
        $TLV_Data .=pack('nndnn',14,$this->Request_id,0,0,0);
        $TLV_Data .=pack('ncvnva*',1,0,0,1,(strlen($message)+1),$message);
        $TLV_Data .=pack('H*', '0000000000FFFFFF00');
        $this->PackBody  = pack('nnnNdnca*',4,6,0,$this->Request_id,$cookie,2,strlen($uin),$uin);
        $this->PackBody .= pack('nna*',5,strlen($TLV_Data), $TLV_Data);
        $this->PackBody .= pack('nna*',3,0,'');
        fwrite($this->sock, $this->GetFlap());
    }

    function SendSSI($uin, $reason)
    {
        $this->Request_id++;
        $this->Flap['chanel2'] = 2;
        $this->PackBody = pack('nnnN',19,20,0,$this->Request_id);
        $this->PackBody .= pack('ca*',strlen($uin),$uin);
        $this->PackBody .= pack('n*',0,0,0);
        $this->Request_id++;
        fwrite($this->sock, $this->GetFlap());
        $this->PackBody = pack('nnnN',19,24,0,$this->Request_id);
        $this->PackBody .= pack('ca*',strlen($uin),$uin);
        $this->PackBody .= pack('na*',strlen($reason),$reason);
        $this->PackBody .= pack('n',0);
        fwrite($this->sock, $this->GetFlap());
    }
    
    function SendSSIxReply($uin, $reason, $flag = false)
    {
        $this->Request_id++;
        $this->Flap['chanel2'] = 2;
        $this->PackBody = pack('nnnN',19,26,0,$this->Request_id);
        $this->PackBody .= pack('ca*',strlen($uin),$uin);
        $this->PackBody .= pack('cna*',$flag,strlen($reason),$reason);
        fwrite($this->sock, $this->GetFlap());
    }
    
    function icqPing()
    {
        $this->Flap['chanel2'] = 5;
        fwrite($this->sock,$this->GetFlap());
    }
    
    function icqBye()
    {
        $this->PackBody = 0x0000;
        $this->Flap['chanel2'] = 4;
        fwrite($this->sock,$this->getFlap());
        fclose($this->sock);
        $this->sock = 0;
    }
    
    private function ReadPack()
    {
        $this->Flap = fread($this->sock, 6);
        if ($this->Flap) {
            $this->Flap = unpack('c2chanel/n2size',$this->Flap);
        }
        $this->Flap['header'] = fread($this->sock,$this->Flap['size2']);
        return $this->Flap['header'];
    } 
    
    private function GetFlap()
    {
        $this->Sequence++;
        $req = pack('ccnn', 0x2A, $this->Flap['chanel2'],$this->Sequence, strlen($this->PackBody)).$this->PackBody;
        return $req;
    }
    
    function isConnected()
    {    
        if($this->sock){ return true; } else { return false; }
    }
    
    function icqLoop($uin,$_msg)
    {
        $this->SendMsg($uin,$_msg);
        sleep(1);
        $this->SendSSIxReply($uin,"SSSSSSSSSSS",0);
        sleep(1);
        $this->SendSSI($uin,"SendSSI; vas dobavili");
        sleep(1);
        $this->SendSSIxReply($uin,"aaaaaaaaaa",1);
        sleep(1);
        $this->icqPing();    
        $this->icqBye();
    }
}
