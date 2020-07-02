<?php

require_once DIR_SEI_WEB . '/SEI.php';


/**
 * Classe responsável pela verificação da corretação instalação e configuração do módulo no sistema
 */
class MdEstatisticasVerificarRN extends InfraRN
{

    public function __construct() {
        parent::__construct();
    }

    protected function inicializarObjInfraIBanco() {
        return BancoSEI::getInstance();
    }

    /**
     * Verifica se o módulo foi devidamente ativado nas configurações do sistema
     *
     * @return bool
     */
    public function verificarAtivacaoModulo()
    {
        global $SEI_MODULOS;

        if(!array_key_exists("MdEstatisticas", $SEI_MODULOS)){
            throw new InfraException("Chave de ativação do módulo mod-sei-estatisticas (MdEstatisticas) não definido nas configurações de módulos do SEI");
        }

        if(is_null($SEI_MODULOS['MdEstatisticas'])){
            $objConfiguracaoSEI = ConfiguracaoSEI::getInstance();

            if (!$objConfiguracaoSEI->isSetValor('SEI','Modulos')){
                throw new InfraException("Chave de configuração de Módulos não definida nas configurações do sistema. (ConfiguracaoSEI.php | SEI > Modulos)");
            }

            $arrModulos = $objConfiguracaoSEI->getValor('SEI','Modulos');
            $strDiretorioModEstatisticas = basename($arrModulos['MdEstatisticas']);
            $strDiretorioModulos = dirname ($arrModulos['MdEstatisticas']);
            throw new InfraException("Diretório do módulo ($strDiretorioModEstatisticas) não pode ser localizado em $strDiretorioModulos");
        }

        return true;
    }


    /**
    * Verifica a correta definição de todos os parâmetros de configuração do módulo
    *
    * @return bool
    */
    public function verificarArquivoConfiguracao()
    {

        // Verifica se chave de config presente
        $arrPrincipal = $objConfiguracaoSEI->getValor('MdEstatisticas', 'ignorar_arquivos');
        
        // Valida se todos os parâmetros de configuração estão presentes no arquivo de configuração
        $arrStrChavesConfiguracao = ConfiguracaoModPEN::getInstance()->getArrConfiguracoes();
        if(!array_key_exists("PEN", $arrStrChavesConfiguracao)){
            $strMensagem = "Grupo de parametrização 'PEN' não pode ser localizado no arquivo de configuração do módulo de integração do SEI com o Barramento PEN (mod-sei-pen)";
            $strDetalhes = "Verifique se o arquivo de configuração localizado em $strArquivoConfiguracao encontra-se íntegro.";
            throw new InfraException($strMensagem, null, $strDetalhes);
        }


        // Valida se todas as chaves de configuração obrigatórias foram atribuídas
        $arrStrChavesConfiguracao = $arrStrChavesConfiguracao["PEN"];
        $arrStrParametrosExperados = array("WebService", "LocalizacaoCertificado", "SenhaCertificado");
        foreach ($arrStrParametrosExperados as $strChaveConfiguracao) {
            if(!array_key_exists($strChaveConfiguracao, $arrStrChavesConfiguracao)){
                $strMensagem = "Parâmetro 'PEN > $strChaveConfiguracao' não pode ser localizado no arquivo de configuração do módulo de integração do SEI com o Barramento PEN (mod-sei-pen)";
                $strDetalhes = "Verifique se o arquivo de configuração localizado em $strArquivoConfiguracao encontra-se íntegro.";
                throw new InfraException($strMensagem, null, $strDetalhes);
            }
        }

        return true;
    }

    /**
    * Verifica a conexão com o WebService Rest, utilizando o endereço e certificados informados
    *
    * @return bool
    */
    public function verificarConexao()
    {
        $objConfiguracaoModPEN = ConfiguracaoModPEN::getInstance();
        $strEnderecoWebService = $objConfiguracaoModPEN->getValor("PEN", "WebService");
        $strLocalizacaoCertificadoDigital = $objConfiguracaoModPEN->getValor("PEN", "LocalizacaoCertificado");
        $strSenhaCertificadoDigital = $objConfiguracaoModPEN->getValor("PEN", "SenhaCertificado");

        $strEnderecoWSDL = $strEnderecoWebService . '?wsdl';
        $curl = curl_init($strEnderecoWSDL);

        try{
            curl_setopt($curl, CURLOPT_URL, $strEnderecoWSDL);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSLCERT, $strLocalizacaoCertificadoDigital);
            curl_setopt($curl, CURLOPT_SSLCERTPASSWD, $strSenhaCertificadoDigital);

            $strOutput = curl_exec($curl);

            $objXML = simplexml_load_string($strOutput);
            if(is_null($objXML)){
                throw new InfraException("Falha na validação do WSDL do webservice de integração com o Barramento de Serviços do PEN localizado em $strEnderecoWSDL");
            }

        } finally{
            curl_close($curl);
        }

        return true;
    }


    /**
    * Verifica a conexão com o Barramento de Serviços do PEN, utilizando o endereço e certificados informados
    *
    * @return bool
    */
    public function verificarAcessoPendenciasTramitePEN()
    {
        // Processa uma chamada ao Barramento de Serviços para certificar que o atual certificado está corretamente vinculado à um
        // comitê de protocolo válido
        $objProcessoEletronicoRN = new ProcessoEletronicoRN();
        $objProcessoEletronicoRN->listarPendencias(false);
        return true;
    }

    /**
    * Verifica se Gearman foi corretamente configurado e se o mesmo se encontra ativo
    *
    * @return bool
    */
    public function verificarConfiguracaoGearman()
    {
        $objConfiguracaoModPEN = ConfiguracaoModPEN::getInstance();
        $arrObjGearman = $objConfiguracaoModPEN->getValor("PEN", "Gearman", false);
        $strGearmanServidor = trim(@$arrObjGearman["Servidor"] ?: null);
        $strGearmanPorta = trim(@$arrObjGearman["Porta"] ?: null);

        if(empty($strGearmanServidor)) {
            // Não processa a verificação da instalação do Gearman caso não esteja configurado
            return false;
        }

        if(!class_exists("GearmanClient")){
            throw new InfraException("Não foi possível localizar as bibliotecas do PHP para conexão ao GEARMAN./n" .
                "Verifique os procedimentos de instalação do mod-sei-pen para maiores detalhes");
        }

        try{
            $objGearmanClient = new GearmanClient();
            $objGearmanClient->addServer($strGearmanServidor, $strGearmanPorta);
            $objGearmanClient->ping("health");
        } catch (\Exception $e) {
            $strMensagemErro = "Não foi possível conectar ao servidor Gearman (%s, %s). Erro: %s";
            $strMensagem = "Não foi possível conectar ao servidor Gearman ($this->strGearmanServidor, $this->strGearmanPorta). Erro:" . $objGearmanClient->error();
            $strMensagem = sprintf($strMensagemErro, $this->strGearmanServidor, $this->strGearmanPorta, $objGearmanClient->error());
            throw new InfraException($strMensagem);
        }

        return true;
    }

    private function verificarExistenciaArquivo($parStrLocalizacaoArquivo)
    {
        if(!file_exists($parStrLocalizacaoArquivo)){
            $strNomeArquivo = basename($parStrLocalizacaoArquivo);
            $strDiretorioArquivo = dirname($parStrLocalizacaoArquivo);
            throw new InfraException("Arquivo do $strNomeArquivo não pode ser localizado em $strDiretorioArquivo");
        }
    }
}