import React from 'react';

class LoginForm extends React.Component {
  state = {
      username: '',
      password: ''
  }

  handleChange = e => {
    this.setState({
      [e.target.name]: e.target.value
    });
  };

  clearState = () => {
    this.setState({
      username: '',
      password: ''
    })
  };

  checkFields = () => {
    return true;
  };

// TEST GET - OK*/}

  loadPlayers = () => {
    const myInit = { method: 'get',
                    mode: 'no-cors'
                  };

    const url = 'https://www.floriantorres.fr/infostrafootapi/public/players'
    fetch(url)
      .then(function(response, myInit) {
        return response.json();
      })
      .then(function(datas) {
        console.log(datas)
      });    
  }

// TEST POST - PRESQUE OK*/}

  onSubmit = e => {
    e.preventDefault();
    (this.checkFields() && this.props.onSubmit(this.state));
    console.log(JSON.stringify(this.state))

    const myInit = { method: 'post',
                     headers: {
                       'Accept': 'application/json',
                       'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(this.state)
                  };
    const url = 'https://www.floriantorres.fr/infostrafootapi/public/login'

    fetch(url, myInit)
      .then(function(response) {
        return response.json();
      })
      .then(function(datas) {
        console.log(datas)
      })
      .catch((error) => {
        console.error(error)
      });

    this.loadPlayers()

    // Transmitting state to App.onSubmit function
    //this.clearState();
  };

  render() {
    return (

      <form>
        
        <div className="input-group">
            <span className="input-group-addon" id="basic-addon1"><i className="fas fa-user"></i></span>
            <input
              name="username"
              className="form-control"
              aria-describedby="basic-addon1"
              placeholder='Pseudo'
              value={this.state.username}
              onChange={e => this.handleChange(e)}
            />
        </div>

        <div className="input-group">
            <span className="input-group-addon" id="basic-addon2"><i className="fas fa-key"></i></span>
            <input
              name="password"
              className="form-control"
              aria-describedby="basic-addon2"
              placeholder='Mot de passe'
              type="password"
              value={this.state.password}
              onChange={e => this.handleChange(e)}
            />
        </div>

        <button className="btn btn-primary" id="submit" onClick={e => this.onSubmit(e)}>Connexion</button>
        <a href="/signup" className="lien">Pas encore inscrit ?</a>
      </form>
    );
  }
}

export default LoginForm;