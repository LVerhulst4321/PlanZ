import React, { useState } from "react";
import axios from "axios";
import SimpleAlert from "../../common/simpleAlert";

function DevLoginPage() {
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [message, setMessage] = useState(null);

  const login = () => {
    axios
      .post("/api/authenticate.php", { userid: username, password: password })
      .then((res) => {
        setMessage({
          text: "login success, now you can navigate to other parts of the dev site",
          severity: "success",
        });
      })
      .catch((error) => {
        setMessage({ text: "login not successful", severity: "danger" });
      });
  };

  return (
    <form
      id="login-form"
      name="loginform"
      className="form-horizontal"
      method="post"
    >
      <SimpleAlert message={message} />
      <div className="card">
        <div className="card-body">
          <h2 className="card-title">✨ Login ✨</h2>
          <div className="form-group">
            <input
              type="text"
              placeholder="user"
              value={username}
              onChange={(e) => setUsername(e.target.value)}
              title="Enter your {$USER_ID_PROMPT}"
            />
          </div>
          <div className="form-group">
            <input
              type="password"
              placeholder="Password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              title="Enter your password"
            />
          </div>
          <button
            onClick={() => login()}
            className="btn btn-primary"
            title="Click to log in"
            type="button"
          >
            Dev Login
          </button>
          <div>
            <a href="ForgotPassword.php">New user or forgot your password</a>
          </div>
          <div>
            <a href="ForgotPassword.php">Forgot your password</a>
          </div>
        </div>
      </div>
    </form>
  );
}

export default DevLoginPage;
