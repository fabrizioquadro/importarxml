@extends('layouts.app_admin')
@section('content')
    <div class="container-fluid p-0">
        <div class="row">
            <div class="col-auto d-none d-sm-block">
                <h1 class="h3 mb-3 d-none d-md-inline-flex">Imports</h1>
                <h1 class="h4 mb-3 d-md-none">Imports</h1>
            </div>
            <div class="col-auto d-md-inline-flex ms-auto text-end mt-n1">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{route('importsxml.index')}}">Imports</a></li>
                    <li class="breadcrumb-item active">Cadastrar</li>
                </ol>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow vld-parent">
                    <div class="card-body">
                        <form  action="{{ route('importsxml.store') }}" method="post" enctype="multipart/form-data">
                            @csrf
                            <div class="row mb-3">
                                <div class="col-md-6 form-group">
                                    <label for="">Tipo:</label>
                                    <select required name="tp_import" id="tp_import" class="form-control">
                                        <option value=""></option>
                                        <option value="Produtos e Serviços">Produtos e Serviços</option>
                                        <option value="Destinatários">Destinatários</option>
                                    </select>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="">Arquivo</label>
                                    <input type="file" id='arquivo' name="arquivo" accept=".zip, .xml" class="form-control">
                                </div>
                            </div>
                            <div class="d-grid gap-2 d-md-block">
                                <input type="button" value="Importar" class="btn  btn-primary col-xs-12 col-md-2 me-md-2" onclick="importarXml()">
                                <a href="{{ route('importsxml.index') }}" class="btn btn-secondary btn-block col-xs-12 col-md-2" role="button">Voltar</a>
                            </div>
                            <div class="row" id='carregamento' style='display: none'>
                                <div class="col-md-12">
                                    <span id='spanCarregamento'>Carregando 0%</span>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
    function importarXml(){
        if(document.getElementById('tp_import').value != "" && document.getElementById('arquivo').value != ""){
            const form = document.querySelector('form');
            const request = new XMLHttpRequest();

            document.getElementById('carregamento').style.display = 'block'
            const formData = new FormData(form);
            request.open("POST", "{{ route('importsxml.store') }}");
            request.send(formData);

            request.upload.addEventListener("progress", e =>{
                const { loaded, total } = e;
                const percent = (loaded / total) * 100;

                if(percent < 100){
                    document.getElementById('spanCarregamento').innerHTML = "Carregando " + Math.trunc(percent) + " %";
                }
                else{
                    document.getElementById('spanCarregamento').innerHTML = "Processando os Xmls anexos... <br> Não feche esta tela pois esta ação ocasionará erro no sistema.";
                }
            })

            request.onreadystatechange = ()=>{
                if(request.readyState == 4){
                    if(request.status == 200){
                        document.getElementById('carregamento').style.display = 'none'
                        alert('Processo de Importação Finalizado!!!');
                        window.location.href = "{{ route('importsxml.index') }}";
                    }
                    else if(request.status != 200){
                        document.getElementById('spanCarregamento').innerHTML = "Ocorreu um erro ma inserção dos dados, contate o administrador";
                    }
                }
            }
        }
        else{
            alert('É necessário escolher um arquivo e o tipo de importação.');
        }

    }
    </script>
@endsection
