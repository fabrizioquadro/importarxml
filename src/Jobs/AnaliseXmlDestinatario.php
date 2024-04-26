<?php

namespace fabrizioquadro\importarxml\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use fabrizioquadro\importarxml\Models\Import;
use fabrizioquadro\importarxml\Models\Import_Arquivo;
use App\Models\Pessoa;
use App\Models\Endereco;

class AnaliseXmlDestinatario implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected $ID_IMPORT)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $import = Import::where('ID', $this->ID_IMPORT)->first();

        $arquivos = Import_Arquivo::where('ID_IMPORT', $import->ID)
        ->where('STATUS', "Fila de Processamento")
        ->get();

        //vamos buscar o cnpj da empresa na tabela pessoa
        $pessoa_base = Pessoa::where('ID_EMPRESA', $import->ID_EMPRESA)->first();

        //vamos fazer a leitura de cada arquivo
        foreach ($arquivos as $arquivo){
            //vamos montar uma variavel de retorno onde estará concatenado o que já esta no banco de dados
            $retorno = $arquivo->RETORNO."Data: ".date('d/m/Y H:i:s');
            //vamos analizar se a extenção desse arquivo é xml
            $var_arquivo = explode('.', $arquivo->ARQUIVO);
            if(strtolower($var_arquivo[1]) != "xml"){
                $dados_arquivo = [
                    'STATUS' => "Erro",
                    'RETORNO' => "Este arquivo não esta com o nome padrão dos xmls ou não é um arquivo com estensão xml",
                ];
            }
            else{
                //vamos carregar o xml em uma variavel
                $xml = simplexml_load_file(public_path('importsXML/'.$import->ID_EMPRESA."/".$import->PASTA."/".$arquivo->ARQUIVO));

                //vamos analisar se este é um arquivo nfe ou nfse
                if(property_exists($xml, 'NFe')){
                    //vamos analizar se este xml é da empresa
                    $emit_cnpj = $xml->NFe->infNFe->emit->CNPJ;
                    //if($emit_cnpj != $pessoa_base->NU_CNPJ_CPF){
                    //    $dados_arquivo = [
                    //        'STATUS' => "Erro",
                    //        'RETORNO' => "Este xml não pertence a empresa.",
                    //    ];
                    //}
                    //else{

                        $destinatario = $xml->NFe->infNFe->dest;
                        if(property_exists($destinatario, 'CNPJ')){
                            $ID_TIPO_PESSOA = '1';
                            $cnpj_cpf = $destinatario->CNPJ;
                        }
                        elseif(property_exists($destinatario, 'CPF')){
                            $ID_TIPO_PESSOA = '0';
                            $cnpj_cpf = $destinatario->CPF;
                        }

                        //vamos verificar se já existe esse destinatario na base de dados
                        $dados_pesquisa = [
                            'ID_EMPRESA' => $import->ID_EMPRESA,
                            'NU_CNPJ_CPF' => $cnpj_cpf,
                        ];

                        if(Pessoa::where($dados_pesquisa)->count() > 0){
                            $retorno .= "<br>Destinatário: ".$destinatario->xNome." já existe na base de dados.";
                        }
                        else{
                            //entrando aqui vamos cadastrar

                            if($destinatario->indIEDest == '1'){
                                $ID_INDICADOR_IE = '0';
                            }
                            elseif($destinatario->indIEDest == '2'){
                                $ID_INDICADOR_IE = '1';
                            }
                            elseif($destinatario->indIEDest == '9'){
                                $ID_INDICADOR_IE = '2';
                            }

                            $dados_pessoa = [
                                'ID_EMPRESA' => $import->ID_EMPRESA,
                                'ID_TIPO_PESSOA' => $ID_TIPO_PESSOA,
                                'ID_INDICADOR_IE' => $ID_INDICADOR_IE,
                                'DS_RAZAO_SOCIAL' => $destinatario->xNome,
                                'NU_CNPJ_CPF' => $cnpj_cpf,
                                'NU_INSC_MUNICIPAL' => $destinatario->IM,
                                'NU_INSC_ESTADUAL' => $destinatario->IE,
                                'DS_EMAIL' => $destinatario->enderDest->email,
                                'DT_CADASTRO' => date('Y-m-d'),
                                'DS_RAZAO_SOCIAL_UPPER' => strtoupper($destinatario->xNome),
                                'TP_CONSUMIDOR_FINAL' => '0',
                                'ID_PAI' => '0',
                                'ID_PESSOA_QF' => '0',
                                'IND_PROD_RURAL' => '0',
                            ];

                            $pessoa = Pessoa::create($dados_pessoa);

                            //vamos inserir os dados no endereço
                            $dados_endereco = [
                                'ID_PESSOA' => $pessoa->id,
                                'ID_MUNICIPIO' => $destinatario->enderDest->cMun,
                                'DS_LOGRADOURO' => $destinatario->enderDest->xLgr,
                                'DS_NUMERO' => $destinatario->enderDest->nro,
                                'DS_COMPLEMENTO' => $destinatario->enderDest->xCpl,
                                'DS_CEP' => $destinatario->enderDest->CEP,
                                'NU_TELEFONE' => $destinatario->enderDest->fone,
                                'NU_CELULAR' => $destinatario->enderDest->fone,
                                'NO_CONTATO' => NULL,
                                'PAIS_ID' => $destinatario->enderDest->cPais,
                            ];

                            Endereco::create($dados_endereco);

                            $retorno .= "<br> Destinatário ".$destinatario->xNome." cadastrado no banco de dados";
                        }

                        $dados_arquivo = [
                            'STATUS' => "Finalizado",
                            'RETORNO' => $retorno,
                        ];
                    //}
                }
                elseif(property_exists($xml, 'Nfse')){
                    $cnpj = $xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->Prestador->CpfCnpj->Cnpj;
                    //if($cnpj != $pessoa_base->NU_CNPJ_CPF){
                    //    $dados_arquivo = [
                    //        'STATUS' => "Erro",
                    //        'RETORNO' => "Este xml não pertence a empresa, nem como emitente nem como destinatário.",
                    //    ];
                    //}
                    //else{
                        $tomador = $xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->TomadorServico;
                        $controleCpfCnpj = $tomador->IdentificacaoTomador->CpfCnpj;

                        if(property_exists($controleCpfCnpj, 'Cnpj')){
                            $cnpj_cpf = $tomador->IdentificacaoTomador->CpfCnpj->Cnpj;
                            $ID_TIPO_PESSOA = '1';
                        }
                        elseif(property_exists($tomador, 'Cpf')){
                            $cnpj_cpf = $tomador->IdentificacaoTomador->CpfCnpj->Cpf;
                            $ID_TIPO_PESSOA = '0';
                        }

                        //vamos verificar se já existe esse destinatario na base de dados
                        $dados_pesquisa = [
                            'ID_EMPRESA' => $import->ID_EMPRESA,
                            'NU_CNPJ_CPF' => $cnpj_cpf,
                        ];

                        if(Pessoa::where($dados_pesquisa)->count() > 0){
                            $retorno .= "<br>Destinatário: ".$tomador->RazaoSocial." já existe na base de dados.";
                        }
                        else{
                            $dados_pessoa = [
                                'ID_EMPRESA' => $import->ID_EMPRESA,
                                'ID_TIPO_PESSOA' => $ID_TIPO_PESSOA,
                                'ID_INDICADOR_IE' => NULL,
                                'DS_RAZAO_SOCIAL' => $tomador->RazaoSocial,
                                'NU_CNPJ_CPF' => $cnpj_cpf,
                                'NU_INSC_MUNICIPAL' => $tomador->IdentificacaoTomador->InscricaoMunicipal,
                                'NU_INSC_ESTADUAL' => $tomador->IdentificacaoTomador->InscricaoEstadual,
                                'DS_EMAIL' => $tomador->Contato->Email,
                                'DT_CADASTRO' => date('Y-m-d'),
                                'DS_RAZAO_SOCIAL_UPPER' => strtoupper($tomador->RazaoSocial),
                                'TP_CONSUMIDOR_FINAL' => '0',
                                'ID_PAI' => '0',
                                'ID_PESSOA_QF' => '0',
                                'IND_PROD_RURAL' => '0',
                            ];

                            $pessoa = Pessoa::create($dados_pessoa);

                            //vamos inserir os dados no endereço
                            $dados_endereco = [
                                'ID_PESSOA' => $pessoa->id,
                                'ID_MUNICIPIO' => $tomador->Endereco->CodigoMunicipio,
                                'DS_LOGRADOURO' => $tomador->Endereco->Endereco,
                                'DS_NUMERO' => $tomador->Endereco->Numero,
                                'DS_COMPLEMENTO' => $tomador->Endereco->Complemento,
                                'DS_CEP' => $tomador->Endereco->Cep,
                                'NU_TELEFONE' => $tomador->Contato->Telefone,
                                'NU_CELULAR' => $tomador->Contato->Telefone,
                                'NO_CONTATO' => NULL,
                                'PAIS_ID' => NULL,
                            ];

                            Endereco::create($dados_endereco);

                            $retorno .= "<br> Destinatário ".$tomador->RazaoSocial." cadastrado no banco de dados";
                        }

                        $dados_arquivo = [
                            'STATUS' => "Finalizado",
                            'RETORNO' => $retorno,
                        ];
                    //}
                }
            }
            //aqui atualizamos o arquivo
            Import_Arquivo::where('ID', $arquivo->ID)->update($dados_arquivo);
        }

        $dados_import = [
            'ANDAMENTO' => 'Arquivos Processados',
            'STATUS' => 'Finalizado',
        ];

        Import::where('ID', $import->ID)->update($dados_import);
    }
}
