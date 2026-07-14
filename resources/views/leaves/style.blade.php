<style>
    .custom-gap .list-group-item {
        margin: 0px;
        padding: 2px;
    }
    .profile-image-container {
        width: 100px !important;
        height: 100px !important;
        border-radius: 50% !important;
        overflow: hidden !important;
        display: inline-block !important;
        position: relative !;
    }

    .profile-image-container img {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
        border-radius: 50% !important;
    }

    .mtop {
        margin-top: -15px;
    }
    .bg-form{
        background-color:  #e9ecef;
    }

    /* Control and button sizes now come from css/hris-theme.css so that the
       leave pages match the rest of the system. */
    .form-control-sm {
        background-color: #ffffff !important;
    }
    .select2-container--default.select2-container--disabled .select2-selection--single {
        background-color: #ffffff;
        cursor: default;
    }
    .input-details {
        border: none;
        border-bottom: 1px solid #8f7f7f;
        padding: 0;
        outline: none;
        box-shadow: none;
        width: 220px;
    }
    .c-radio{
        width: 20px; 
        height: 20px; 
        padding-top: 2px;
        width: 20px; 
        height: 20px; 
        padding-top: 2px;
    }
    .c-label{
        border-radius: 3px; 
        padding: 2px; 
        width: 90px; 
        display: inline-block; 
        background-color: #FFFF;
    }

    .ft{
        font-size: 10px;
    }

    .bg-default{
        background-color: #3B8682 !important;
    }

    .glowing {
        border: 2px solid;
        animation: glowing 3.5s infinite;
    }

    @keyframes glowing {
        0% {
            border-color: #fff;
            box-shadow: 0 0 5px #fff;
        }
        50% {
            border-color: #00ff00;
            box-shadow: 0 0 10px #00ff00;
        }
        100% {
            border-color: #fff;
            box-shadow: 0 0 5px #fff;
        }
    }

    .download{
        margin-left: 59px !important;
        margin-top:  19px !important;
    }
</style>