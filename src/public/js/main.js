document.addEventListener("DOMContentLoaded", function () {
  const emailRadio = document.querySelector('input[value="email"]');
  const phoneRadio = document.querySelector('input[value="phone"]');
  const emailField = document.getElementById("email_field");
  const phoneField = document.getElementById("phone_field");

  emailRadio.addEventListener("change", function () {
    if (this.checked) {
      emailField.style.display = "block";
      phoneField.style.display = "none";
      document.getElementById("email").required = true;
      document.getElementById("phone").required = false;
    }
  });

  phoneRadio.addEventListener("change", function () {
    if (this.checked) {
      emailField.style.display = "none";
      phoneField.style.display = "block";
      document.getElementById("phone").required = true;
      document.getElementById("email").required = false;
    }
  });
});

function togglePassword() {
  const passwordField = document.getElementById("password");
  passwordField.type = passwordField.type === "password" ? "text" : "password";
}

document
  .getElementById("registerForm")
  .addEventListener("submit", function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    const otpType = document.querySelector(
      'input[name="register_type"]:checked',
    ).value;

    fetch("../api/auth/register.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        console.log("Server Response:", data);

        if (data.status === true) {
          fetch("../api/auth/send_otp.php", {
            method: "POST",
            body: new URLSearchParams({
              user_id: data.data.user_id,
              otp_type: otpType,
            }),
          })
            .then((r) => r.json())
            .then((res) => {
              console.log("OTP Response:", res);

              if (res.status) {
                alert("OTP sent! Check your email/phone to verify.");
                window.location.href =
                  "verify_otp.html?user_id=" + data.data.user_id;
              } else {
                alert("Failed to send OTP: " + res.message);
              }
            })
            .catch((err) => {
              console.error(err);
              alert("OTP sending failed");
            });
        } else {
          alert("Error: " + data.message);
        }
      })
      .catch((err) => {
        console.error(err);
        alert("Something went wrong. Please try again.");
      });
  });
