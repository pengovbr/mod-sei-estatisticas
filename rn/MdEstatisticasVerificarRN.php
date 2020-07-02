<?php

require_once dirname(__FILE__) . '/../../../SEI.php';

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

        // Valida se todos os parâmetros de configuração estão presentes no arquivo de configuração
        $arrStrChavesConfiguracao = ConfiguracaoSEI::getInstance()->getArrConfiguracoes();
        if(!array_key_exists("MdEstatisticas", $arrStrChavesConfiguracao)){
            $strMensagem = "Grupo de parametrização MdEstatisticas nao pode ser localizado no arquivo de configuração do SEI";
            $strDetalhes = "Verifique se o arquivo de configuração encontra-se íntegro.";
            throw new InfraException($strMensagem, null, $strDetalhes);
        }

        // Valida se todas as chaves de configuração obrigatórias foram atribuídas
        $arrStrChavesConfiguracao = $arrStrChavesConfiguracao["MdEstatisticas"];
        $arrStrParametrosExperados = array("url", "sigla", "chave");
        foreach ($arrStrParametrosExperados as $strChaveConfiguracao) {
            if(!array_key_exists($strChaveConfiguracao, $arrStrChavesConfiguracao)){
                $strMensagem = "Parâmetro 'MdEstatisticas > $strChaveConfiguracao' não pode ser localizado no arquivo de configuração";
                $strDetalhes = "Verifique se o arquivo de configuração  encontra-se íntegro.";
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
        $objConfiguracaoSEI = ConfiguracaoSEI::getInstance();
        $url = $objConfiguracaoSEI->getValor('MdEstatisticas', 'url');        
        $urlApi = $url . '/api/estatisticas';
        $urllogin = $url . '/login';
        $orgaoSigla = $objConfiguracaoSEI->getValor('MdEstatisticas', 'sigla', false, '');
        $orgaoSenha = $objConfiguracaoSEI->getValor('MdEstatisticas', 'chave', false, '');
        $header = array('Content-Type: application/json');
        
        $json = array(
            username => $orgaoSigla,
            password => $orgaoSenha
        );
        $data = json_encode($json);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $urllogin);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HEADER, true);
        $output = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        if ($info['http_code'] == 200) {
            $output = explode("\r\n", $output);
            foreach ($output as $value) {
                if (strpos(strtoupper($value), 'AUTHORIZATION') !== false) {
                    $this->header[] = $value;
                    return true;
                }
            }
        }
        
        //se chegou ate aqui deu problema
        throw new InfraException("Falha ao autenticar http code " . $info['http_code'] . ". Caso o http code seja 200 verifique se o token Authorization está presente " . print_r($output, false));
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
