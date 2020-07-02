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
        $orgaoSigla = $objConfiguracaoSEI->getValor('MdEstatisticas', 'sigla');
        $orgaoSenha = $objConfiguracaoSEI->getValor('MdEstatisticas', 'chave');
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
        $msg = "Falha ao autenticar. " . 
               "Url: " . $urllogin . 
               " Sigla: " . $orgaoSigla . 
               " Chave: " . $orgaoSenha . 
               " Valor do Http code: " . $info['http_code'] . 
               ". Caso o http code seja diferente de 200 houve alguma falha na conexao. " .
               "Verifique a rota e se o seu php consegue acessar o servidor configurado no campo url. " . 
               "Caso o http code seja 403 significa que foi barrado no webservice. Verifique url, sigla e chave. " . 
               "Caso o http code seja 200 verifique se o token Authorization está presente. " .
               "Caso ele nao esteja presente significa que nao conseguiu fazer o login. Reveja a url, sigla e chave usadas. " . 
               "Output do Curl: " . print_r($output, true);
        throw new InfraException($msg);
    }


    /**
    * Verifica leitura dos hashs
    *
    * @return bool
    */
    public function verificarLeituraHashs()
    {
        $coletor = new MdEstatisticasColetarRN();
        $filesHash = $coletor->obterHashs(); 
        
        return $filesHash;
    }

}
