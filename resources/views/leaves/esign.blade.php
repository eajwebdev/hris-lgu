@extends('layouts.master')

@section('body')

<script type="text/javascript" src="https://na1.foxitesign.foxit.com/js/esignGeniePostMessageParent.js"></script>
<section class="content">
    <div class="container-fluid">

        <div style="height: 100%; width: 100%; overflow: auto;" data-role="content">
            <iframe id="esignIframe" src="https://na1.foxitesign.foxit.com/embedded/embeddedsign?eetid={URL-ENCODED-EMBEDDED-TOKEN}" style="width: 99% !important; height: 99% !important; position: absolute;" frameborder="0"></iframe>
        </div>
        
    </div>
</section>
    
@endsection
