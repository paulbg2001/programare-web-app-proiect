import { useState } from "react";
import "./App.css";

function App() {
  const [isRegister, setIsRegister] = useState(false);
  const [isLoggedIn, setIsLoggedIn] = useState(false);
  const [activePage, setActivePage] = useState("dashboard");
  const [error, setError] = useState("");

  const [vehicles, setVehicles] = useState([
    {
      id: 1,
      plate: "SB 20 ABC",
      vin: "WVWZZZ1JZXW000001",
      brand: "Volkswagen",
      model: "Passat",
      year: 2018,
      fuel: "Diesel",
      status: "Activ",
    },
    {
      id: 2,
      plate: "SB 44 TRK",
      vin: "VF1AAAAAA12345678",
      brand: "Renault",
      model: "Master",
      year: 2020,
      fuel: "Diesel",
      status: "Activ",
    },
    {
      id: 3,
      plate: "B 99 LOG",
      vin: "TMBAAAAAA98765432",
      brand: "Skoda",
      model: "Octavia",
      year: 2017,
      fuel: "Benzina",
      status: "Inactiv",
    },
  ]);

  const [formVehicle, setFormVehicle] = useState({
    plate: "",
    vin: "",
    brand: "",
    model: "",
    year: "",
    fuel: "",
    status: "Activ",
  });

  const [editingId, setEditingId] = useState(null);

  const documents = [
    {
      id: 1,
      vehicle: "SB 20 ABC",
      type: "RCA",
      expiryDate: "2026-05-10",
    },
    {
      id: 2,
      vehicle: "SB 44 TRK",
      type: "ITP",
      expiryDate: "2026-06-10",
    },
    {
      id: 3,
      vehicle: "B 99 LOG",
      type: "Rovinieta",
      expiryDate: "2026-09-15",
    },
  ];

  const drivers = [
    {
      id: 1,
      name: "Ion Popescu",
      license: "SB123456",
      phone: "0712345678",
      status: "Activ",
    },
    {
      id: 2,
      name: "Mihai Ionescu",
      license: "SB987654",
      phone: "0722333444",
      status: "Inactiv",
    },
  ];

  const ferrariModels = [
    { name: "12Cilindri", years: "2024 - prezent" },
    { name: "296", years: "2022 - prezent" },
    { name: "458", years: "2009 - 2016" },
    { name: "488", years: "2015 - 2019" },
    { name: "812", years: "2017 - 2024" },
    { name: "Enzo", years: "2001 - 2005" },
    { name: "F40", years: "1987 - 1992" },
    { name: "F50", years: "1995 - 1997" },
    { name: "F8", years: "2019 - 2023" },
    { name: "LaFerrari", years: "2013 - 2017" },
    { name: "Portofino", years: "2017 - 2023" },
    { name: "Purosangue", years: "2022 - prezent" },
    { name: "Roma", years: "2020 - prezent" },
    { name: "SF90", years: "2019 - prezent" },
    { name: "Testarossa", years: "1984 - 1996" },
  ];

  function getDocumentStatus(expiryDate) {
    const today = new Date();
    const expiry = new Date(expiryDate);
    const diffTime = expiry - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays < 0) return "expired";
    if (diffDays <= 30) return "soon";
    return "valid";
  }

  function getStatusText(status) {
    if (status === "expired") return "Expirat";
    if (status === "soon") return "Expira curand";
    return "Valid";
  }

  const expiredDocuments = documents.filter(
    (doc) => getDocumentStatus(doc.expiryDate) === "expired"
  ).length;

  const activeVehicles = vehicles.filter(
    (vehicle) => vehicle.status === "Activ"
  ).length;

  const activeAssignments = 2;

  function handleAuthSubmit(e) {
    e.preventDefault();
    setError("");

    const form = e.target;
    const email = form.email.value.trim();
    const password = form.password.value.trim();
    const username = isRegister ? form.username.value.trim() : "";
    const confirmPassword = isRegister
      ? form.confirmPassword.value.trim()
      : "";

    if (isRegister && username === "") {
      setError("Username-ul este obligatoriu.");
      return;
    }

    if (email === "") {
      setError("Email-ul este obligatoriu.");
      return;
    }

    if (!email.includes("@")) {
      setError("Email invalid.");
      return;
    }

    if (password === "") {
      setError("Parola este obligatorie.");
      return;
    }

    if (isRegister && password !== confirmPassword) {
      setError("Parolele nu coincid.");
      return;
    }

    setIsLoggedIn(true);
    setActivePage("dashboard");
  }

  function logout() {
    setIsLoggedIn(false);
    setIsRegister(false);
    setError("");
  }

  function handleVehicleChange(e) {
    const { name, value } = e.target;

    setFormVehicle({
      ...formVehicle,
      [name]: value,
    });
  }

  function saveVehicle(e) {
    e.preventDefault();

    if (
      formVehicle.plate === "" ||
      formVehicle.vin === "" ||
      formVehicle.brand === "" ||
      formVehicle.model === "" ||
      formVehicle.year === "" ||
      formVehicle.fuel === ""
    ) {
      alert("Completeaza toate campurile obligatorii.");
      return;
    }

    const duplicatePlate = vehicles.find(
      (v) =>
        v.plate.toLowerCase() === formVehicle.plate.toLowerCase() &&
        v.id !== editingId
    );

    const duplicateVin = vehicles.find(
      (v) =>
        v.vin.toLowerCase() === formVehicle.vin.toLowerCase() &&
        v.id !== editingId
    );

    if (duplicatePlate) {
      alert("Exista deja un vehicul cu acest numar de inmatriculare.");
      return;
    }

    if (duplicateVin) {
      alert("Exista deja un vehicul cu acest VIN.");
      return;
    }

    if (editingId) {
      setVehicles(
        vehicles.map((vehicle) =>
          vehicle.id === editingId
            ? {
                ...formVehicle,
                id: editingId,
              }
            : vehicle
        )
      );

      setEditingId(null);
    } else {
      setVehicles([
        ...vehicles,
        {
          ...formVehicle,
          id: Date.now(),
        },
      ]);
    }

    setFormVehicle({
      plate: "",
      vin: "",
      brand: "",
      model: "",
      year: "",
      fuel: "",
      status: "Activ",
    });
  }

  function editVehicle(vehicle) {
    setFormVehicle(vehicle);
    setEditingId(vehicle.id);
  }

  function deleteVehicle(id) {
    const confirmDelete = window.confirm(
      "Sigur vrei sa marchezi vehiculul ca inactiv?"
    );

    if (!confirmDelete) return;

    setVehicles(
      vehicles.map((vehicle) =>
        vehicle.id === id
          ? {
              ...vehicle,
              status: "Inactiv",
            }
          : vehicle
      )
    );
  }

  if (!isLoggedIn) {
    return (
      <div className="auth-page">
        <div className="auth-box">
          <h1>{isRegister ? "Create Account" : "Welcome Back !"}</h1>

          <form onSubmit={handleAuthSubmit}>
            {isRegister && (
              <>
                <label>Username</label>
                <input
                  type="text"
                  name="username"
                  placeholder="Enter username"
                />
              </>
            )}

            <label>Email Address</label>
            <input type="email" name="email" placeholder="Enter email" />

            <label>Password</label>
            <input
              type="password"
              name="password"
              placeholder="Enter password"
            />

            {isRegister && (
              <>
                <label>Confirm Password</label>
                <input
                  type="password"
                  name="confirmPassword"
                  placeholder="Confirm password"
                />
              </>
            )}

            {!isRegister && (
              <a href="#" className="forgot">
                Forgot Password?
              </a>
            )}

            {error && <div className="error-message">{error}</div>}

            <button type="submit">
              {isRegister ? "Register" : "Login"}
            </button>

            <p>
              {isRegister
                ? "Already have an account?"
                : "Are you a new member?"}{" "}
              <a
                href="#"
                onClick={(e) => {
                  e.preventDefault();
                  setIsRegister(!isRegister);
                  setError("");
                }}
              >
                {isRegister ? "Login" : "Sign Up"}
              </a>
            </p>
          </form>
        </div>
      </div>
    );
  }

  return (
    <div className="app">
      <aside className="sidebar">
        <h2>FleetApp</h2>

        <nav>
          <button
            className={activePage === "dashboard" ? "active" : ""}
            onClick={() => setActivePage("dashboard")}
          >
            Dashboard
          </button>

          <button
            className={activePage === "vehicles" ? "active" : ""}
            onClick={() => setActivePage("vehicles")}
          >
            Vehicule
          </button>

          <button
            className={activePage === "drivers" ? "active" : ""}
            onClick={() => setActivePage("drivers")}
          >
            Soferi
          </button>

          <button
            className={activePage === "documents" ? "active" : ""}
            onClick={() => setActivePage("documents")}
          >
            Documente
          </button>

          <button
            className={activePage === "maintenance" ? "active" : ""}
            onClick={() => setActivePage("maintenance")}
          >
            Mentenanta
          </button>

          <button
            className={activePage === "tires" ? "active" : ""}
            onClick={() => setActivePage("tires")}
          >
            Anvelope
          </button>

          <button
            className={activePage === "profile" ? "active" : ""}
            onClick={() => setActivePage("profile")}
          >
            Profil
          </button>

          <button
            className={activePage === "ferrari" ? "active" : ""}
            onClick={() => setActivePage("ferrari")}
          >
            Ferrari Info
          </button>
        </nav>
      </aside>

      <main className="main">
        <header className="header">
          <div>
            <h1>
              {activePage === "dashboard" && "Dashboard"}
              {activePage === "vehicles" && "Vehicule"}
              {activePage === "drivers" && "Soferi"}
              {activePage === "documents" && "Documente"}
              {activePage === "maintenance" && "Mentenanta"}
              {activePage === "tires" && "Anvelope"}
              {activePage === "profile" && "Profil"}
              {activePage === "ferrari" && "Ferrari Info"}
            </h1>
          </div>

          <div className="user-area">
            <span>Nicolae Zglatar</span>
            <button onClick={logout}>Logout</button>
          </div>
        </header>

        <section className="content">
          {activePage === "dashboard" && (
            <>
              <div className="cards">
                <div className="card">
                  <h3>Vehicule active</h3>
                  <p>{activeVehicles}</p>
                </div>

                <div className="card">
                  <h3>Documente expirate</h3>
                  <p>{expiredDocuments}</p>
                </div>

                <div className="card">
                  <h3>Alocari active</h3>
                  <p>{activeAssignments}</p>
                </div>
              </div>

              <div className="panel">
                <h2>Documente flota</h2>

                <table>
                  <thead>
                    <tr>
                      <th>Vehicul</th>
                      <th>Document</th>
                      <th>Data expirare</th>
                      <th>Status</th>
                    </tr>
                  </thead>

                  <tbody>
                    {documents.map((doc) => {
                      const status = getDocumentStatus(doc.expiryDate);

                      return (
                        <tr key={doc.id}>
                          <td>{doc.vehicle}</td>
                          <td>{doc.type}</td>
                          <td>{doc.expiryDate}</td>
                          <td>
                            <span className={`badge ${status}`}>
                              {getStatusText(status)}
                            </span>
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
            </>
          )}

          {activePage === "vehicles" && (
            <>
              <div className="panel">
                <h2>{editingId ? "Editeaza vehicul" : "Adauga vehicul"}</h2>

                <form className="vehicle-form" onSubmit={saveVehicle}>
                  <input
                    name="plate"
                    value={formVehicle.plate}
                    onChange={handleVehicleChange}
                    placeholder="Numar inmatriculare"
                  />

                  <input
                    name="vin"
                    value={formVehicle.vin}
                    onChange={handleVehicleChange}
                    placeholder="Serie VIN"
                  />

                  <input
                    name="brand"
                    value={formVehicle.brand}
                    onChange={handleVehicleChange}
                    placeholder="Marca"
                  />

                  <input
                    name="model"
                    value={formVehicle.model}
                    onChange={handleVehicleChange}
                    placeholder="Model"
                  />

                  <input
                    name="year"
                    value={formVehicle.year}
                    onChange={handleVehicleChange}
                    placeholder="An fabricatie"
                  />

                  <input
                    name="fuel"
                    value={formVehicle.fuel}
                    onChange={handleVehicleChange}
                    placeholder="Combustibil"
                  />

                  <select
                    name="status"
                    value={formVehicle.status}
                    onChange={handleVehicleChange}
                  >
                    <option>Activ</option>
                    <option>Inactiv</option>
                  </select>

                  <button type="submit">
                    {editingId ? "Salveaza modificarile" : "Adauga vehicul"}
                  </button>
                </form>
              </div>

              <div className="panel">
                <h2>Lista vehicule</h2>

                <table>
                  <thead>
                    <tr>
                      <th>Nr. inmatriculare</th>
                      <th>VIN</th>
                      <th>Marca</th>
                      <th>Model</th>
                      <th>An</th>
                      <th>Combustibil</th>
                      <th>Status</th>
                      <th>Actiuni</th>
                    </tr>
                  </thead>

                  <tbody>
                    {vehicles.map((vehicle) => (
                      <tr key={vehicle.id}>
                        <td>{vehicle.plate}</td>
                        <td>{vehicle.vin}</td>
                        <td>{vehicle.brand}</td>
                        <td>{vehicle.model}</td>
                        <td>{vehicle.year}</td>
                        <td>{vehicle.fuel}</td>
                        <td>{vehicle.status}</td>
                        <td>
                          <button
                            className="small-btn"
                            onClick={() => editVehicle(vehicle)}
                          >
                            Edit
                          </button>

                          <button
                            className="small-btn danger"
                            onClick={() => deleteVehicle(vehicle.id)}
                          >
                            Sterge
                          </button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </>
          )}

          {activePage === "drivers" && (
            <div className="panel">
              <h2>Lista soferi</h2>

              <table>
                <thead>
                  <tr>
                    <th>Nume</th>
                    <th>Numar permis</th>
                    <th>Telefon</th>
                    <th>Status</th>
                  </tr>
                </thead>

                <tbody>
                  {drivers.map((driver) => (
                    <tr key={driver.id}>
                      <td>{driver.name}</td>
                      <td>{driver.license}</td>
                      <td>{driver.phone}</td>
                      <td>{driver.status}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}

          {activePage === "documents" && (
            <div className="panel">
              <h2>Documente</h2>

              <table>
                <thead>
                  <tr>
                    <th>Vehicul</th>
                    <th>Tip document</th>
                    <th>Data expirare</th>
                    <th>Status</th>
                  </tr>
                </thead>

                <tbody>
                  {documents.map((doc) => {
                    const status = getDocumentStatus(doc.expiryDate);

                    return (
                      <tr key={doc.id}>
                        <td>{doc.vehicle}</td>
                        <td>{doc.type}</td>
                        <td>{doc.expiryDate}</td>
                        <td>
                          <span className={`badge ${status}`}>
                            {getStatusText(status)}
                          </span>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          )}

          {activePage === "maintenance" && (
            <div className="panel">
              <h2>Mentenanta</h2>
              <p className="empty-text">
                Aici vor fi afisate operatiunile de mentenanta.
              </p>
            </div>
          )}

          {activePage === "tires" && (
            <div className="panel">
              <h2>Anvelope</h2>
              <p className="empty-text">
                Aici va fi gestiunea pentru inventarul de anvelope.
              </p>
            </div>
          )}

          {activePage === "profile" && (
            <div className="panel">
              <h2>Profil utilizator</h2>
              <p className="empty-text">
                Utilizator conectat: Nicolae Zglatar
              </p>
            </div>
          )}

          {activePage === "ferrari" && (
            <>
              <div className="panel">
                <h2>Ferrari - Informatii generale</h2>

                <p className="info-text">
                  Ferrari este o marca italiana cunoscuta pentru automobile
                  sport, supercaruri si performanta ridicata. Masinile Ferrari
                  sunt asociate cu motoare puternice, design aerodinamic si
                  tehnologie inspirata din motorsport.
                </p>

                <div className="info-grid">
                  <div className="info-box">
                    <h3>Tara</h3>
                    <p>Italia</p>
                  </div>

                  <div className="info-box">
                    <h3>Oras productie</h3>
                    <p>Maranello</p>
                  </div>

                  <div className="info-box">
                    <h3>Tip vehicule</h3>
                    <p>Sport / Supercar</p>
                  </div>

                  <div className="info-box">
                    <h3>Tehnologie</h3>
                    <p>Benzina / Hybrid</p>
                  </div>
                </div>
              </div>

              <div className="panel">
                <h2>Modele Ferrari</h2>

                <div className="models-grid">
                  {ferrariModels.map((model, index) => (
                    <div className="model-card" key={index}>
                      <h3>{model.name}</h3>
                      <p>{model.years}</p>
                    </div>
                  ))}
                </div>
              </div>

              <div className="panel">
                <h2>Observatii</h2>

                <p className="info-text">
                  Ferrari are si modele hibride, cum ar fi LaFerrari, SF90 si
                  296. Acestea combina motorul clasic cu motoare electrice
                  pentru performanta mai mare si eficienta imbunatatita.
                </p>

                <p className="source-text">Sursa informatii: Auto-Data.net</p>
              </div>
            </>
          )}
        </section>
      </main>
    </div>
  );
}

export default App;