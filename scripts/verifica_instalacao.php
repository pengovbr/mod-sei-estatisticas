<?php
require_once dirname(__FILE__) . '/../../../SEI.php';

// Garante que codigo abaixo foi executado unicamente via linha de comando
if ($argv && $argv[0] && realpath($argv[0]) === __FILE__) {
    InfraDebug::getInstance()->setBolLigado(true);
    InfraDebug::getInstance()->setBolDebugInfra(false);
    InfraDebug::getInstance()->setBolEcho(true);
    InfraDebug::getInstance()->limpar();

    $resultado = 0;

    $fnPrint = function($strMensagem, $numIdentacao=0) {
        DebugEstatisticas::getInstance()->gravar($strMensagem, $numIdentacao, false, false);
    };


    try {
        SessaoSEI::getInstance(false);
        
        
        $fnPrint("INICIANDO VERIFICACAO DA INSTALACAO DO MODULO MOD-SEI-ESTATISTICAS:", 0);

        try{
            $objMdEstatisticasVerificarRN = new MdEstatisticasVerificarRN();
        }catch(Exception $e){
            $fnPrint("- Nao foi possivel instanciar a classe, verifique se o modulo esta configurado no ConfiguracaoSEI.php. Maiores detalhes do erro abaixo", 1);
        }

        sleep(1);
        if($objMdEstatisticasVerificarRN->verificarAtivacaoModulo()){
            $fnPrint("- Modulo corretamente ativado no arquivo de configuracao do sistema", 1);
        }


        sleep(1);
        if($objMdEstatisticasVerificarRN->verificarArquivoConfiguracao()){
            $fnPrint("- Chaves obrigatorias no arquivo de configuracao estao preenchidas (url,sigla e chave) ", 1);
        }

        sleep(1);
        if($objMdEstatisticasVerificarRN->verificarConexao()){
            $fnPrint("- Conexao com o WebService realizada com sucesso", 1);
        }

        sleep(1);
        $fnPrint("- Vamos agora iniciar a leitura dos hashs." , 1);
        $fnPrint("  Se necessario, certifique-se de ler e entender na documentacao do repositorio sobre a variavel opcional ignorar_arquivos, ", 1);
        $fnPrint("  caso junto do sei voce tenha na pasta do Apache outros diretorios ou sistemas. ", 1);
        $fnPrint("  Ressalva: prestar atencao ao usuario que esta executando esse script pois ao ler os arquivos via agendamento quem", 2);
        $fnPrint("  executa sera o user do crontab e via web sera o apache ", 2);
        $fnPrint("", 0);
        $fnPrint("", 0);
        $fnPrint("", 0);
        
        $fnPrint("- Iniciando leitura agora, aguarde... ", 1);
        
        $r = $objMdEstatisticasVerificarRN->verificarLeituraHashs();        

        if(is_array($r)){
            $fnPrint("- Leitura de Hashs realizada", 1);
            
            $fnPrint("- Foi calculado o hash de " . count($r) . " arquivos. ", 1);
        }else{
            throw new InfraException("Falha ao ler o hash dos arquivos. Verifique permissoes no diretorio e tambem a necessidade de configurar a variavel ignorar_arquivos");           
        }

        $fnPrint("", 0);
        $fnPrint("** VERIFICACAO INICIAL DA INSTALACAO DO MODULO DE ESTATISTICAS FINALIZADA **", 0);
        
        $fnPrint("", 0);
        $fnPrint("", 0);
        $fnPrint("", 0);
        $fnPrint("", 0);
        $fnPrint("AGORA VAMOS TENTAR ENVIAR UMA LEITURA COMPLETA PARA O WEB SERVICE COLETOR:", 0);
        
        $enviar = new MdEstatisticasEnviarRN();

        $fnPrint('Autenticar no WebService', 1);
        if (!$enviar->autenticar()) {
            throw new InfraException('Problemas com a autenticacao.');
        }

        $fnPrint('Autenticado. Coletando indicadores', 1);

        $coletor = new MdEstatisticasColetarRN();
        $indicadores = $coletor->coletarIndicadores();

        $fnPrint('Indicadores coletados, enviando', 1);

        $saida = $enviar->enviarIndicadores($indicadores);

        $id = $saida['id'];

        if (!$id) {
            throw new InfraException('Erro no envio dos indicadores.');
        }

        $fnPrint('Indicadores recebidos. Coletar indicadores do tipo lista', 1);

        $fnPrint('Obter a data do ultimo envio das quantidades de acessos ', 1);
        $data = $enviar->obterUltimoAcesso();
        $fnPrint('Ultima data das quantidades de acessos: ' . $data . '. Coletar quantidade de acessos', 1);

        $acessos = $coletor->obterAcessosUsuarios($data);
        $fnPrint('Coletado. Enviar quantidade de acessos: ', 1);
        $enviar->enviarAcessos($acessos, $id);

        $fnPrint('Enviado. Coletar velocidades por cidade: ', 1);
        $velocidades = $coletor->obterVelocidadePorCidade();
        $fnPrint('Coletado. Enviar: ', 1);
        $enviar->enviarVelocidades($velocidades, $id);

        $fnPrint('Enviado. Obter a data do ultimo envio das quantidades de navegadores ', 1);
        $data = $enviar->obterUltimoNavegador();
        $fnPrint('Ultima data das quantidades de navegadores: ' . $data . '. Coletar quantidade de navegadores', 1);

        $navegadores = $coletor->obterNavegadores($data);
        $fnPrint('Coletado. Enviar: ', 1);
        $enviar->enviarNavegadores($navegadores, $id);

        $fnPrint('Enviado. Coletar a quantidade de logs de erro: ', 1);
        $logs = $coletor->obterQuantidadeLogErro();
        $fnPrint('Coletado. Enviar: ', 1);
        $enviar->enviarLogsErro($logs, $id);

        $fnPrint('Enviado. Obter a ultima data que foi enviado a quantidade de recursos ', 1);
        $dataultimorecurso = $enviar->obterUltimoRecurso();
        $fnPrint('Ultima data das quantidades de recursos: ' . $dataultimorecurso . '. Coletar quantidade de recursos', 1);
        $recursos = $coletor->obterQuantidadeRecursos($dataultimorecurso);
        $fnPrint('Coletado. Enviar: ', 1);
        $enviar->enviarRecursos($recursos, $id);

        $fnPrint('Enviado. Coletar o hash dos arquivos do SEI: ', 1);
        $filesHash = $coletor->obterHashs();
        $fnPrint('Coletado. Enviar: ', 1);
        $enviar->enviarHashs($filesHash, $id);

        $fnPrint('Enviado: ', 1);
        
        $fnPrint('Apenas Coletar velocidades por cidade novamente ', 1);
        $velocidades = $coletor->obterVelocidadePorCidade();
        $fnPrint('Coletado ', 1);

        $fnPrint('FINALIZADO COM SUCESSO. NAO ESQUECA DE AGENDAR NO MENU INFRA -> AGENDAMENTO DO SEI E VERIFICAR SE O AGENDAMENTO ESTA RODANDO', 0);

        exit(0);
    } finally {
        InfraDebug::getInstance()->setBolLigado(false);
        InfraDebug::getInstance()->setBolDebugInfra(false);
        InfraDebug::getInstance()->setBolEcho(false);
    }
}
