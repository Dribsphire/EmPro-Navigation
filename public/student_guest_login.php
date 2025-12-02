<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login-Empro::</title>
    <link rel="icon" type="image/png" href="images/logo2.png">
    <link rel="stylesheet" type="text/css" href="css/login_student_guest.css" />
  
  </head>
  <body>
    <div class="container">
      <div class="forms-container">
        <div class="signin-signup">
          <form action="" class="sign-in-form">
            <h2 class="title">Sign In</h2>
            <div class="input-field">
              <i class="fas fa-user"></i>
              <input type="text" placeholder="School ID" />
            </div>
            <div class="input-field">
              <i class="fas fa-lock"></i>
              <input type="password" placeholder="Password" />
            </div>
            <input type="submit" value="Login" class="btn solid" />

            <!--<p class="social-text">Or Sign in with social platforms</p>
            <div class="social-media">
              <a href="#" class="social-icon">
                <i class="fab fa-facebook-f"></i>
              </a>
              <a href="#" class="social-icon">
                <i class="fab fa-twitter"></i>
              </a>
              <a href="#" class="social-icon">
                <i class="fab fa-google"></i>
              </a>
              <a href="#" class="social-icon">
                <i class="fab fa-linkedin-in"></i>
              </a>
            </div>-->
          </form>


          <form action="" class="sign-up-form">
            <h2 class="title">Sign Up</h2>
            <div class="input-field">
              <i class="fas fa-user"></i>
              <input type="text" placeholder="Full name" />
            </div>
            <div class="input-field" style="max-width: 380px;
            width: 100%;
            background-color: #f0f0f0;
            margin: 10px 0;
            height: 55px;
            border-radius: 20px;
            display: grid;
            grid-template-columns: 15% 85%;
            padding: 0 0.4rem;
            position: relative;
            height: 6rem;">
              <i class="fas fa-envelope"></i>
              <input type="text" id="reason_summary" name="reason_summary" placeholder="Enter a brief reason here">
              
            </div>
            <input type="submit" value="Navigate" class="btn solid" />
          </form>
        </div>
      </div>
      <div class="panels-container">

        <div class="panel left-panel">
            <div class="content">
                <h3>EmPro</h3>
                <p>A simple, intuitive tool that helps students and staff quickly access school schedules, resources, and essential information in one place.</p>
                <button class="btn transparent" id="sign-up-btn">I'm a Guest</button>
            </div>
            <img src="images/CHMSU.png" class="image" alt="" style="width:300px; height:300px;align-self: center;">
        </div>

        <div class="panel right-panel">
            <div class="content">
                <h3>EmPro</h3>
                <p>A simple, intuitive tool that helps students and staff quickly access school schedules, resources, and essential information in one place.</p>
                <button class="btn transparent" id="sign-in-btn">I'm a Student</button>
            </div>
            <img src="images/CHMSU.png" class="image" alt="" style="width:300px; height:300px;align-self: center;">
        </div>
      </div>
    </div>

    <script>
        const sign_in_btn = document.querySelector("#sign-in-btn");
        const sign_up_btn = document.querySelector("#sign-up-btn");
        const container = document.querySelector(".container");

        sign_up_btn.addEventListener('click', () =>{
            container.classList.add("sign-up-mode");
        });

        sign_in_btn.addEventListener('click', () =>{
            container.classList.remove("sign-up-mode");
        });
    </script>
  </body>
</html>