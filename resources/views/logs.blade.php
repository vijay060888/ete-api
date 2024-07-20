<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="icon" type="image/x-icon" href="{{url('favicon.png')}}">
        <title>Nxt Gov LOG</title>

        <!-- Fonts -->
        <link href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
        <!-- Styles -->
        <style>
            /*! normalize.css v8.0.1 | MIT License | github.com/necolas/normalize.css */html{line-height:1.15;-webkit-text-size-adjust:100%}body{margin:0}a{background-color:transparent}[hidden]{display:none}html{font-family:system-ui,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica Neue,Arial,Noto Sans,sans-serif,Apple Color Emoji,Segoe UI Emoji,Segoe UI Symbol,Noto Color Emoji;line-height:1.5}*,:after,:before{box-sizing:border-box;border:0 solid #e2e8f0}a{color:inherit;text-decoration:inherit}svg,video{display:block;vertical-align:middle}video{max-width:100%;height:auto}.bg-white{--bg-opacity:1;background-color:#fff;background-color:rgba(255,255,255,var(--bg-opacity))}.bg-gray-100{--bg-opacity:1;background-color:#f7fafc;background-color:rgba(247,250,252,var(--bg-opacity))}.border-gray-200{--border-opacity:1;border-color:#edf2f7;border-color:rgba(237,242,247,var(--border-opacity))}.border-t{border-top-width:1px}.flex{display:flex}.grid{display:grid}.hidden{display:none}.items-center{align-items:center}.justify-center{justify-content:center}.font-semibold{font-weight:600}.h-5{height:1.25rem}.h-8{height:2rem}.h-16{height:4rem}.text-sm{font-size:.875rem}.text-lg{font-size:1.125rem}.leading-7{line-height:1.75rem}.mx-auto{margin-left:auto;margin-right:auto}.ml-1{margin-left:.25rem}.mt-2{margin-top:.5rem}.mr-2{margin-right:.5rem}.ml-2{margin-left:.5rem}.mt-4{margin-top:1rem}.ml-4{margin-left:1rem}.mt-8{margin-top:2rem}.ml-12{margin-left:3rem}.-mt-px{margin-top:-1px}.max-w-6xl{max-width:72rem}.min-h-screen{min-height:100vh}.overflow-hidden{overflow:hidden}.p-6{padding:1.5rem}.py-4{padding-top:1rem;padding-bottom:1rem}.px-6{padding-left:1.5rem;padding-right:1.5rem}.pt-8{padding-top:2rem}.fixed{position:fixed}.relative{position:relative}.top-0{top:0}.right-0{right:0}.shadow{box-shadow:0 1px 3px 0 rgba(0,0,0,.1),0 1px 2px 0 rgba(0,0,0,.06)}.text-center{text-align:center}.text-gray-200{--text-opacity:1;color:#edf2f7;color:rgba(237,242,247,var(--text-opacity))}.text-gray-300{--text-opacity:1;color:#e2e8f0;color:rgba(226,232,240,var(--text-opacity))}.text-gray-400{--text-opacity:1;color:#cbd5e0;color:rgba(203,213,224,var(--text-opacity))}.text-gray-500{--text-opacity:1;color:#a0aec0;color:rgba(160,174,192,var(--text-opacity))}.text-gray-600{--text-opacity:1;color:#718096;color:rgba(113,128,150,var(--text-opacity))}.text-gray-700{--text-opacity:1;color:#4a5568;color:rgba(74,85,104,var(--text-opacity))}.text-gray-900{--text-opacity:1;color:#1a202c;color:rgba(26,32,44,var(--text-opacity))}.underline{text-decoration:underline}.antialiased{-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}.w-5{width:1.25rem}.w-8{width:2rem}.w-auto{width:auto}.grid-cols-1{grid-template-columns:repeat(1,minmax(0,1fr))}@media (min-width:640px){.sm\:rounded-lg{border-radius:.5rem}.sm\:block{display:block}.sm\:items-center{align-items:center}.sm\:justify-start{justify-content:flex-start}.sm\:justify-between{justify-content:space-between}.sm\:h-20{height:5rem}.sm\:ml-0{margin-left:0}.sm\:px-6{padding-left:1.5rem;padding-right:1.5rem}.sm\:pt-0{padding-top:0}.sm\:text-left{text-align:left}.sm\:text-right{text-align:right}}@media (min-width:768px){.md\:border-t-0{border-top-width:0}.md\:border-l{border-left-width:1px}.md\:grid-cols-2{grid-template-columns:repeat(2,minmax(0,1fr))}}@media (min-width:1024px){.lg\:px-8{padding-left:2rem;padding-right:2rem}}@media (prefers-color-scheme:dark){.dark\:bg-gray-800{--bg-opacity:1;background-color:#2d3748;background-color:rgba(45,55,72,var(--bg-opacity))}.dark\:bg-gray-900{--bg-opacity:1;background-color:#1a202c;background-color:rgba(26,32,44,var(--bg-opacity))}.dark\:border-gray-700{--border-opacity:1;border-color:#4a5568;border-color:rgba(74,85,104,var(--border-opacity))}.dark\:text-white{--text-opacity:1;color:#fff;color:rgba(255,255,255,var(--text-opacity))}.dark\:text-gray-400{--text-opacity:1;color:#cbd5e0;color:rgba(203,213,224,var(--text-opacity))}.dark\:text-gray-500{--tw-text-opacity:1;color:#6b7280;color:rgba(107,114,128,var(--tw-text-opacity))}}
        </style>

        <style>
            body {
                font-family: 'Nunito', sans-serif;
                background: #e3ebff !important;
            }

           /*  .btn{
                color: #fff;
                background: #307cf5;
                padding: 20px 15px;
                display: inline-flex;
                cursor: pointer;
                align-items: center;
                justify-content: center;
                border-radius: 0.375rem;
                border-width: 1px;
                padding-top: 0.5rem;
                padding-bottom: 0.5rem;
                padding-left: 0.75rem;
                padding-right: 0.75rem;
                font-weight: 500;
                margin-top: 20px;
            } */
            /* .form-control {
                width: 100%;
                border-radius: 0.375rem;
                --tw-border-opacity: 1;
                border:1px solid rgb(226 232 240 / 1) !important;
                font-size: 0.875rem;
                line-height: 1.25rem;
                --tw-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
                --tw-shadow-colored: 0 1px 2px 0 var(--tw-shadow-color);
                box-shadow: var(--tw-ring-offset-shadow, 0 0 #0000), var(--tw-ring-shadow, 0 0 #0000), var(--tw-shadow);
                transition-property: color, background-color, border-color, fill, stroke, opacity, box-shadow, transform, filter, -webkit-text-decoration-color, -webkit-backdrop-filter;
                transition-property: color, background-color, border-color, text-decoration-color, fill, stroke, opacity, box-shadow, transform, filter, backdrop-filter;
                transition-property: color, background-color, border-color, text-decoration-color, fill, stroke, opacity, box-shadow, transform, filter, backdrop-filter, -webkit-text-decoration-color, -webkit-backdrop-filter;
                transition-duration: 200ms;
                transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
                padding-top: 0.75rem;
                padding-bottom: 0.75rem;
                padding-left: 1rem;
                padding-right: 1rem;
            } */
        </style>
    </head>
    <body class="antialiased">
        <div class="relative  justify-center  mt-4 py-4 sm:pt-0">
            <div class=" mx-auto sm:px-6 lg:px-8" style="max-width:40%">
                <div class=" text-center pt-8 sm:pt-0">
                    <img src="" class="h-16 w-auto text-gray-700 sm:h-20">
                </div>

                <div class="mt-8 bg-white dark:bg-gray-800 overflow-hidden shadow sm:rounded-lg">
                    <div class="grid grid-cols-1 md:grid-cols-1">
                        <div class="p-6">
                            <div class="flex items-center" style="">
                                <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" class="w-8 h-8 "><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                <div class="ml-4 text-lg leading-7 font-semibold underline">Log</div>
                            </div>

                            <div class="ml-12">
                                <div class="mt-2 text-gray-600 dark:text-gray-400 text-sm">
                                <input type="text" id="token" class="form-control" placeholder="Enter Token here" />

                                </div>
                                <div class="text-center">
                                    <button style="background-color: #005C99;color:white" id="ajaxSubmit" class="btn  mt-4">Check Log</button>
                                </div>
                                
                            </div>
                        </div>

                        
                    </div>
                </div>
            </div>
        </div>
        <div class="relative justify-center mt-4 py-4 sm:pt-0 data" style="display:one;">
            <div class=" mx-auto sm:px-6 lg:px-8" style="max-width:90%">
               
                <div class="mt-8 bg-white dark:bg-gray-800 overflow-hidden shadow sm:rounded-lg">
                    <div class="grid grid-cols-1 md:grid-cols-1">

                        <div class="p-6">
                            <button type="button" class="btn btn-danger float-end m-4" id="clearData">Clear Log</button>
                            <table class="table  table-striped table-hover table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>MESSAGE</th>
                                        <th>URL</th>
                                        <th>METHOD</th>
                                        <th>IP</th>
                                        <th>USER ID</th>
                                        <th>ADDED ON</th>
                                    </tr>
                                </thead>
                                <tbody>
                               
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
        </script>
        <script>
            var url={!! json_encode(url('/')) !!};
           
                 $(document).ready(function(){
                    $('#ajaxSubmit').click(function(e){
                       e.preventDefault();
                        var token=$("#token").val();
                       $.ajax({
                            url: url+'/api/logs' ,
                            headers: {
                                'Authorization': `Bearer ${token}`,
                            },
                            method: 'GET',
                            success: function(data){
                              if(data.length>0){
                                var html='';
                                for(var i=0,j=1;i<data.length;i++,j++){
                                    html+="<tr>"+
                                            "<td>"+j+"</td>"+
                                            "<td>"+data[i]['subject']+"</td>"+
                                            "<td>"+data[i]['url']+"</td>"+
                                            "<td>"+data[i]['method']+"</td>"+
                                            "<td>"+data[i]['ip']+"</td>"+
                                            "<td>"+data[i]['user_id']+"</td>"+
                                            "<td>"+new Date(data[i]['created_at']).toLocaleString()+"</td>"+
                                            "</tr>";
                                }
                                $("tbody").html(html);
                              }
                            }
                          });
                    });
                    });

                    $('#clearData').click(function(e){
                       e.preventDefault();
                        var token=$("#token").val();
                       $.ajax({
                            url: url+'/api/clearLogs' ,
                            headers: {
                                'Authorization': `Bearer ${token}`,
                            },
                            method: 'GET',
                            success: function(data){
                              if(data){
                                window.location.reload();
                              }
                            }
                          });
                    });
               
        </script>
    </body>
</html>
