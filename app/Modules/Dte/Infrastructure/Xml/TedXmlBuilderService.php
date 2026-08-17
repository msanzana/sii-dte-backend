<?php
namespace App\Modules\Dte\Infrastructure\Xml;

class TedXmlBuilderService
{
    public function buildDdXml(array $data): string
    {
        $xml = '';
        $xml.= '<DD>';
        $xml.= '<RE>'.$this->escape($data['re']).'</RE>';
        $xml.= '<TD>'.$this->escape($data['td']).'</TD>';
        $xml.= '<F>'.$this->escape($data['f']).'</F>';
        $xml.= '<FE>'.$this->escape($data['fe']).'</FE>';
        $xml.= '<RR>'.$this->escape($data['rr']).'</RR>';
        $xml.= '<RSR>'.$this->escape($data['rsr']).'</RSR>';
        $xml.= '<MNT>'.$this->escape($data['mnt']).'</MNT>';
        $xml.= '<IT1>'.$this->escape($data['it1']).'</IT1>';
        $xml.= $data['caf_xml_fragment'];
        $xml.= '<TSTED>'.$this->escape($data['tsted']).'</TSTED>';
        $xml.= '</DD>';

        return $xml;
    }

    public function buildTedXml(array $data, string $frmtBase64): string
    {
        $ddXml = $this->buildDdXml($data);

        $xml = '';
        $xml .= '<TED version="1.0">';
        $xml .= $ddXml;
        $xml .= '<FRMT algoritmo="SHA1withRSA">'.$this->escape($frmtBase64).'</FRMT>';
        $xml .= '</TED>';

        return $xml;
    }
    private function escape(string $value):string
    {
        return htmlspecialchars($value,ENT_XML1 | ENT_COMPAT,'UTF-8');
    }
}
