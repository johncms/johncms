<!DOCTYPE html>
<html lang="<?= $locale ?>">
<head>
        <title><?= $email_title ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <style type="text/css">
        body, table, td, a {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        table, td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }

        img {
            -ms-interpolation-mode: bicubic;
        }

        img {
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }

        table {
            border-collapse: collapse !important;
        }

        body {
            height: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }

        a[x-apple-data-detectors] {
            color: inherit !important;
            text-decoration: none !important;
            font-size: inherit !important;
            font-family: inherit !important;
            font-weight: inherit !important;
            line-height: inherit !important;
        }

        u + #body a {
            color: inherit;
            text-decoration: none;
            font-size: inherit;
            font-family: inherit;
            font-weight: inherit;
            line-height: inherit;
        }

        #MessageViewBody a {
            color: inherit;
            text-decoration: none;
            font-size: inherit;
            font-family: inherit;
            font-weight: inherit;
            line-height: inherit;
        }

        a {
            color: #55514c;
            font-weight: 600;
            text-decoration: underline;
        }

        a:hover {
            color: #000000 !important;
            text-decoration: none !important;
        }

        @media screen and (min-width: 600px) {
            h1 {
                font-size: 48px !important;
                line-height: 48px !important;
            }

            .intro {
                font-size: 24px !important;
                line-height: 36px !important;
            }
        }
    </style>
</head>
<body style="margin: 0 !important; padding: 0 !important;">
<div role="article" aria-label="<?= $config['copyright'] ?>" lang="<?= $locale ?>"
     style="background-color: white; color: #2b2b2b; font-family: 'Avenir Next', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'; font-size: 18px; font-weight: 400; line-height: 28px; margin: 0 auto; max-width: 720px; padding: 40px 20px 40px 20px;">
    <header>
        <a href="<?= $config['homeurl'] ?>">
            <center><img src="<?= $config['homeurl'] ?><?= asset('images/logo.png') ?>" alt="" height="55" width="55"></center>
        </a>
        <h1 style="color: #000000; font-size: 32px; font-weight: 800; line-height: 32px; margin: 48px 0; text-align: center;">
            <?= $email_title ?>
        </h1>
    </header>

    <!-- Main content section. Main is a useful landmark element. -->
    <main>
        <div style="background-color: ghostwhite; border-radius: 4px; padding: 24px 48px;">
            @yield('content')
        </div>
    </main>

    <!-- Footer information. Footer is a useful landmark element. -->
    <footer>
        <p style="font-size: 14px; font-weight: 400; line-height: 24px; margin-top: 48px; text-align: center;">
            <?= __('The email is generated automatically. Please don\'t answer it. <br> To contact us, use the contact information on our website.') ?><br>
            <a href="<?= $config['homeurl'] ?>"><?= $config['copyright'] ?></a>
        </p>
    </footer>
</div>
</body>
</html>
