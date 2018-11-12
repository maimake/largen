<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{$message->getSubject()}}</title>
    <link href="https://cdn.bootcss.com/bulma/0.7.2/css/bulma.min.css" rel="stylesheet">

    <style>
        .nav {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .brand {
            color: #4a4a4a;
            text-decoration: none !important;
            padding: 1rem;
            display: flex;
            justify-content: center;
            align-items: center;
        }
    </style>

</head>
<body>

<div class="nav is-light has-background-light">
    <a class="brand" href="{{config('app.url')}}">
        <img src="{{ $message->embed(public_path('images/mail_logo.png')) }}">
        {{config('app.name')}}
    </a>
</div>

<section class="section">
    <div class="container">
        <h1 class="title">Introduction</h1>

        <p>The body of your message.</p>

        <a class="button is-link">Button Text</a>

        <div class="section">
            Thanks,<br>
            {{ config('app.name') }}
        </div>
    </div>
</section>

<footer class="footer">
    <div class="content has-text-centered">
        <p>
            © 2018 {{ config('app.name') }}. All rights reserved.
        </p>
    </div>
</footer>

</body>
</html>
