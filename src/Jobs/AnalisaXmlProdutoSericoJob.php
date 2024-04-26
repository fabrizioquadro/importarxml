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
use App\Models\Item;
use App\Models\Grupo_Item;
use App\Models\Tipo_Imposto;
use App\Models\Imposto;

class AnalisaXmlProdutoSericoJob implements ShouldQueue
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
        //vamos buscar os dados do import
        $import = Import::where('ID', $this->ID_IMPORT)->first();

        //vamos buscar todos os arquivos que ainda não foram processados dentro desse import
        $arquivos = Import_Arquivo::where('ID_IMPORT', $import->ID)
        ->where('STATUS', "Fila de Processamento")
        ->get();

        //vamos buscar o cnpj da empresa na tabela pessoa
        $pessoa = Pessoa::where('ID_EMPRESA', $import->ID_EMPRESA)->first();

        //vamos fazer a leitura de cada arquivo
        foreach ($arquivos as $arquivo){
            //vamos montar uma variavel de retorno onde estará concatenado o que já esta no banco de dados
            $retorno = $arquivo->RETORNO."Data: ".date('d/m/Y H:i:s');

            //vamos analizar se a extenção desse arquivo é xml
            $var_arquivo = explode('.', $arquivo->ARQUIVO);
            if(strtolower($var_arquivo[1]) != "xml"){
                $dados_arquivo = [
                    'STATUS' => "Erro",
                    'RETORNO' => "Este arquivo não esta com o nome padrão dos xmls ou não é um arquivo com extensão xml",
                ];
            }
            else{
                //vamos carregar o xml em uma variavel
                $xml = simplexml_load_file(public_path('importsXML/'.$import->ID_EMPRESA."/".$import->PASTA."/".$arquivo->ARQUIVO));

                //vamos analisar se este é um arquivo nfe ou nfse
                if(property_exists($xml, 'NFe')){
                    //vamos analizar se este xml é da empresa tanto no destinatario quanto no emitente
                    $emit_cnpj = $xml->NFe->infNFe->emit->CNPJ;
                    $dest_cnpj = $xml->NFe->infNFe->dest->CNPJ;
                    //if($emit_cnpj != $pessoa->NU_CNPJ_CPF && $dest_cnpj != $pessoa->NU_CNPJ_CPF){
                    //    $dados_arquivo = [
                    //        'STATUS' => "Erro",
                    //        'RETORNO' => "Este xml não pertence a empresa, nem como emitente nem como destinatário.",
                    //    ];
                    //}
                    //else{
                        //vamos montar um array com os todos os produtos do xml e percorrer 1 a 1
                        $produtos = $xml->NFe->infNFe->det;
                        foreach ($produtos as $produto){
                            //vamos verificar se este produto já existe na base de dados
                            $dados_pesquisa = [
                                'DS_CODIGO' => $produto->prod->cProd,
                                'DS_DESCRICAO_UPPER' => strtoupper($produto->prod->xProd),
                                'ID_TIPO_ITEM' => '1',
                            ];
                            if(Item::where($dados_pesquisa)->count() > 0){
                                $retorno .= "<br> Produto ".$produto->prod->xProd.", já existente na base de dados";
                            }
                            else{
                                //vamos verificar se é um dos cfops permitidos pelo sistema simples nacional
                                $cfop = $produto->prod->CFOP;
                                if($cfop != '5102' && $cfop != '6102' && $cfop != '7102'){
                                    $retorno .= "<br> Produto ".$produto->prod->xProd.", não possui os cfops 5102, 6102 ou 7102. Contate os administradores para resolução do problema";
                                }
                                else{
                                    //vamos verificar se o grupo existe para este produto
                                    $dados_pesquisa = [
                                        'ID_EMPRESA' => $import->ID_EMPRESA,
                                        'ID_TIPO_ITEM' => '1',
                                        'DS_GRUPO' => 'VENDA - IMPORTACAO CFOP '.$cfop,
                                    ];

                                    if(Grupo_Item::where($dados_pesquisa)->count() > 0){
                                        $grupo = Grupo_Item::where($dados_pesquisa)->first();
                                        $ID_GRUPO = $grupo->ID;
                                    }
                                    else{
                                        //se entrar aqui vamos criar o grupo, o imposto e o tipo imposto
                                        $dados_grupo = [
                                            'ID_EMPRESA' => $import->ID_EMPRESA,
                                            'ID_TIPO_ITEM' => '1',
                                            'DS_GRUPO' => 'VENDA - IMPORTACAO XML '.$cfop,
                                            'ID_INCENTIVO_FISCAL' => '2',
                                            'ID_TIPO_SERVICO' => '1',
                                        ];

                                        $grupo = Grupo_Item::create($dados_grupo);

                                        if($cfop == '5102'){
                                            $DS_TIPO = 'Impostos Estaduais';
                                        }
                                        elseif($cfop == '6102'){
                                            $DS_TIPO = 'Impostos Interestaduais';
                                        }
                                        elseif($cfop == '7102'){
                                            $DS_TIPO = 'Impostos Exterior';
                                        }

                                        $dados_tipo_imposto = [
                                            'DS_TIPO' => $DS_TIPO,
                                        ];

                                        $tipo_imposto = Tipo_Imposto::create($dados_tipo_imposto);

                                        //vamos adicionar as especificações do imposto
                                        $dados_imposto = [
                                            'ID_TIPO' => $tipo_imposto->id,
                                            'ID_GRUPO' => $grupo->id,
                                            'NU_CFOP' => $cfop,
                                            'NU_ALIQ_ICMS' => '0.00',
                                            'NU_ALIQ_ICMSST' => '0.00',
                                            'NU_ALIQ_ICMS_CRED' => '0.00',
                                            'NU_ALIQ_IPI' => '0.00',
                                            'NU_ALIQ_PIS' => '0.00',
                                            'NU_ALIQ_COFINS' => '0.00',
                                            'NU_ALIQ_ISS' => '0.00',
                                            'CO_CST_ICMS' => '102',
                                            'CO_CST_IPI' => '01',
                                            'CO_CST_COFINS' => '99',
                                            'VL_ICMSST_MARGEM_LUCRO' => '0.00',
                                            'VL_ICMSST_REDUCAO' => '0.00',
                                            'CO_ORIGEM_MERCADORIA' => '0',
                                            'CO_BENEF_FIS' => '0',
                                            'NU_ALIQ_ICMS_DESON' => '0.00',
                                            'ID_MOTIVO_DESON_ICMS' => '0',
                                            'CO_ENQUAD_IPI' => '999',
                                            'NU_ALIQ_DIF' => '0.00',
                                        ];

                                        Imposto::create($dados_imposto);

                                        $ID_GRUPO = $grupo->id;
                                    }

                                    //vamos criar o item na base de dados
                                    $dados_item = [
                                        'ID_EMPRESA' => $import->ID_EMPRESA,
                                        'ID_TIPO_ITEM' => '1',
                                        'ID_GRUPO' => $ID_GRUPO,
                                        'CO_ITEM' => NULL,
                                        'CO_EAN' => $produto->prod->cEAN,
                                        'DS_DESCRICAO' => $produto->prod->xProd,
                                        'VL_UNITARIO' => $produto->prod->vUnCom,
                                        'CO_UNID_COMERCIAL' => $produto->prod->uCom,
                                        'NU_NCM' => $produto->prod->NCM,
                                        'DS_CODIGO' => $produto->prod->cProd,
                                        'NU_CEST' => $produto->prod->CEST,
                                        'CO_MED_ANVISA' => $produto->prod->cProdANVISA,
                                        'DS_MOTIVO_IDENCAO' => $produto->prod->xMotivoIsencao,
                                        'DS_DESCRICAO_UPPER' => strtoupper($produto->prod->xProd),
                                    ];

                                    if($produto->prod->vPMC){
                                        $dados_item['VL_PRECO_MAX'] = $produto->prod->vPMC;
                                    }

                                    Item::create($dados_item);

                                    $retorno .= "<br>Produto ".$produto->prod->xProd.", inserido na base de dados";
                                }
                            }
                        }//foreach ($produtos as $produto){

                        $dados_arquivo = [
                            'STATUS' => "Finalizado",
                            'RETORNO' => $retorno,
                        ];
                    //}
                }
                elseif(property_exists($xml, 'Nfse')){
                    //vamos verificar se este xml é da empresa
                    $cnpj = $xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->Prestador->CpfCnpj->Cnpj;
                    //if($cnpj != $pessoa->NU_CNPJ_CPF){
                    //    $dados_arquivo = [
                    //        'STATUS' => "Erro",
                    //        'RETORNO' => "Este xml não pertence a empresa, nem como emitente nem como destinatário.",
                    //    ];
                    //}
                    //else{
                        //vamos montar um array com os todos os servicos do xml e percorrer 1 a 1
                        $servicos = $xml->Nfse->InfNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico->Servico;
                        foreach ($servicos as $servico){
                            //vamos analizar se já existe este serviço no sistema
                            $dados_pesquisa = [
                                'DS_DESCRICAO_UPPER' => strtoupper($servico->Discriminacao),
                                'ID_TIPO_ITEM' => '2',
                            ];
                            if(Item::where($dados_pesquisa)->count() > 0){
                                $retorno .= "<br> Serviço ".$servico->Discriminacao.", já existente na base de dados";
                            }
                            else{
                                //vamos verificar se o grupo existe para este produto
                                $dados_pesquisa = [
                                    'ID_EMPRESA' => $import->ID_EMPRESA,
                                    'ID_TIPO_ITEM' => '2',
                                    'DS_GRUPO' => 'PRESTACAO DE SERVICOS - IMPORTACAO CFOP 5933',
                                ];

                                if(Grupo_Item::where($dados_pesquisa)->count() > 0){
                                    $grupo = Grupo_Item::where($dados_pesquisa)->first();
                                    $ID_GRUPO = $grupo->ID;
                                }
                                else{
                                    //se entrar aqui vamos criar o grupo, o imposto e o tipo imposto
                                    $dados_grupo = [
                                        'ID_EMPRESA' => $import->ID_EMPRESA,
                                        'ID_TIPO_ITEM' => '2',
                                        'DS_GRUPO' => 'PRESTACAO DE SERVICOS - IMPORTACAO CFOP 5933',
                                        'ID_INCENTIVO_FISCAL' => '2',
                                        'ID_TIPO_SERVICO' => '2',
                                    ];

                                    $grupo = Grupo_Item::create($dados_grupo);

                                    $dados_tipo_imposto = [
                                        'DS_TIPO' => 'Impostos Estaduais',
                                    ];

                                    $tipo_imposto = Tipo_Imposto::create($dados_tipo_imposto);

                                    //vamos adicionar as especificações do imposto
                                    $dados_imposto = [
                                        'ID_TIPO' => $tipo_imposto->id,
                                        'ID_GRUPO' => $grupo->id,
                                        'NU_CFOP' => '5933',
                                        'NU_ALIQ_ICMS' => '0.00',
                                        'NU_ALIQ_ICMSST' => '0.00',
                                        'NU_ALIQ_ICMS_CRED' => '0.00',
                                        'NU_ALIQ_IPI' => '0.00',
                                        'NU_ALIQ_PIS' => '0.00',
                                        'NU_ALIQ_COFINS' => '0.00',
                                        'NU_ALIQ_ISS' => '0.00',
                                        'CO_CST_ICMS' => '102',
                                        'CO_CST_IPI' => '01',
                                        'CO_CST_COFINS' => '99',
                                        'VL_ICMSST_MARGEM_LUCRO' => '0.00',
                                        'VL_ICMSST_REDUCAO' => '0.00',
                                        'CO_ORIGEM_MERCADORIA' => '0',
                                        'CO_BENEF_FIS' => '0',
                                        'NU_ALIQ_ICMS_DESON' => '0.00',
                                        'ID_MOTIVO_DESON_ICMS' => '0',
                                        'CO_ENQUAD_IPI' => '999',
                                        'NU_ALIQ_DIF' => '0.00',
                                    ];

                                    Imposto::create($dados_imposto);

                                    $ID_GRUPO = $grupo->id;
                                }

                                //vamos criar o item na base de dados
                                $dados_item = [
                                    'ID_EMPRESA' => $import->ID_EMPRESA,
                                    'ID_TIPO_ITEM' => '2',
                                    'ID_GRUPO' => $ID_GRUPO,
                                    'CO_ITEM' => NULL,
                                    'CO_CEAN' => NULL,
                                    'DS_DESCRICAO' => $servico->Discriminacao,
                                    'VL_UNITARIO' => $servico->Valores->ValorServicos,
                                    'CO_UNID_COMERCIAL' => NULL,
                                    'NU_NCM' => NULL,
                                    'DS_CODIGO' => NULL,
                                    'NU_CEST' => NULL,
                                    'CO_TRIB_MUN' => $servico->CodigoTributacaoMunicipio,
                                    'CO_MED_ANVISA' => NULL,
                                    'DS_MOTIVO_IDENCAO' => NULL,
                                    'DS_DESCRICAO_UPPER' => strtoupper($servico->Discriminacao),
                                    'VL_PRECO_MAX' => NULL,
                                ];

                                Item::create($dados_item);

                                $retorno .= "<br>Servico ".$servico->Discriminacao.", inserido na base de dados";
                            }
                        }

                        $dados_arquivo = [
                            'STATUS' => "Finalizado",
                            'RETORNO' => $retorno,
                        ];
                    //}
                }
                else{
                    $dados_arquivo = [
                        'STATUS' => "Erro",
                        'RETORNO' => "Não foi encontrado um padrão correto no xml analisado.",
                    ];
                }
            }

            Import_Arquivo::where('ID', $arquivo->ID)->update($dados_arquivo);

        }//foreach ($arquivos as $arquivo)

        $dados_import = [
            'ANDAMENTO' => 'Arquivos Processados',
            'STATUS' => 'Finalizado',
        ];

        Import::where('ID', $import->ID)->update($dados_import);
    }
}
