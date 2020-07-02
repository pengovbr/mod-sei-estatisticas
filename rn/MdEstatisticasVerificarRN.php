<?php

require_once dirname(__FILE__) . '/../../../SEI.php';

/**
 * Classe responsavel pela verificacao da correta instalacao e configuracao do modulo no sistema
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
     * Verifica se o modulo foi devidamente ativado nas configuracoes do sistema
     *
     * @return bool
     */
    public function verificarAtivacaoModulo()
    {
        global $SEI_MODULOS;

        if(!array_key_exists("MdEstatisticas", $SEI_MODULOS)){
            throw new InfraException("Chave de ativacao do modulo mod-sei-estatisticas (MdEstatisticas) nao definido nas configuracoes de modulos do SEI");
        }

        if(is_null($SEI_MODULOS['MdEstatisticas'])){
            $objConfiguracaoSEI = ConfiguracaoSEI::getInstance();

            if (!$objConfiguracaoSEI->isSetValor('SEI','Modulos')){
                throw new InfraException("Chave de configuracao de Modulos nao definida nas configuracoes do sistema. (ConfiguracaoSEI.php | SEI > Modulos)");
            }

            $arrModulos = $objConfiguracaoSEI->getValor('SEI','Modulos');
            $strDiretorioModEstatisticas = basename($arrModulos['MdEstatisticas']);
            $strDiretorioModulos = dirname ($arrModulos['MdEstatisticas']);
            throw new InfraException("Diretorio do modulo ($strDiretorioModEstatisticas) nao pode ser localizado em $strDiretorioModulos");
        }

        return true;
    }


    /**
    * Verifica a correta definicao de todos os parametros de configuracao do modulo
    *
    * @return bool
    */
    public function verificarArquivoConfiguracao()
    {

        // Valida se todos os parametros de configuracao estao presentes no arquivo de configuracao
        $arrStrChavesConfiguracao = ConfiguracaoSEI::getInstance()->getArrConfiguracoes();
        if(!array_key_exists("MdEstatisticas", $arrStrChavesConfiguracao)){
            $strMensagem = "Grupo de parametrizacao MdEstatisticas nao pode ser localizado no arquivo de configuracao do SEI. Verifique se o arquivo de configuracao esta de acordo com o manual";
            $strDetalhes = "Verifique se o arquivo de configuracao encontra-se integro.";
            throw new InfraException($strMensagem, null, $strDetalhes);
        }

        // Valida se todas as chaves de configuracao obrigatorias foram atribuídas
        $arrStrChavesConfiguracao = $arrStrChavesConfiguracao["MdEstatisticas"];
        $arrStrParametrosExperados = array("url", "sigla", "chave");
        foreach ($arrStrParametrosExperados as $strChaveConfiguracao) {
            if(!array_key_exists($strChaveConfiguracao, $arrStrChavesConfiguracao)){
                $strMensagem = "Parametro 'MdEstatisticas > $strChaveConfiguracao' nao pode ser localizado no arquivo de configuracao";
                $strDetalhes = "Verifique se o arquivo de configuracao  encontra-se integro.";
                throw new InfraException($strMensagem, null, $strDetalhes);
            }
        }

        return true;
    }

    /**
    * Verifica a conexao com o WebService Rest, utilizando o endereco e certificados informados
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
               "Caso o http code seja 403, significa que foi barrado no webservice. Verifique url, sigla e chave. " . 
               ". Caso o http code seja diferente de 200 houve alguma falha na conexao. " .
               "Verifique a rota e se o seu php consegue acessar o servidor configurado no campo url. " . 
               "Caso o http code seja 200 verifique se o token Authorization esta presente. " .
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
