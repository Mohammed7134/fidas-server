<?php

$_SESSION['actMsg'] = '<style>
body {
    padding: 0;
    margin: 0;
    background-color: white;
    direction: ltr;
}
.container {
    width: 80%;
    margin: auto;
    overflow: hidden;
}

header {
    background: #353637;
    color: #ffffff;
    padding-top: 30px;
    min-height: 70px;
    border-bottom: #32a0c2 4px solid;
}

header #branding h1 {
    margin: 0;
    text-align: center;
}

header .highlight {
    color: #32a0c2;
    font-weight: bold;
}

#newsletter {
    padding: 15px;
    color: #ffffff;
    background: #353637;
}

#newsletter h1{
    text-align: center;
}

footer {
    padding: 20px;
    margin-top: 20px;
    color: #ffffff;
    background-color: #32a0c2;
    text-align: center;
}

@media(max-width: 768px) {
    header #branding, 
    #newsletter h1,
    #newsletter p {
        float: none;
        text-align: center;
        width: 100%;

    }

    header {
        padding-bottom: 20px;
    }
    #showcase h1{
        margin-top: 40px;
    }

}
</style>
<header>
<div class="container">
    <div id="branding">
        <h1>
            <span class="highlight">
            Clinical Pharmacy Activity Application
            </span>
        </h1>
    </div>
</div>
</header>
<section id="newsletter">
<div class="container">
    <h1>Your account is now active</h1>
    <p>
        You can now enjoy using our application.
        <br>
        We hope you find it useful experience.
    </p>
</div>
</section>
<footer>
<p>Clinical Pharmacy Activity &copy; 2021 </p>
</footer>';