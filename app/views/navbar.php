<nav class="navbar navbar-expand-sm navbar-dark bg-dark">
  <div class="container-fluid">
    <!-- <a class="navbar-brand" href="">Logo</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mynavbar">
      <span class="navbar-toggler-icon"></span>
    </button> -->
    <div class="collapse navbar-collapse" id="mynavbar">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link" href="index">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="master">Master</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="attendance">Attendance</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="monthly_report">Monthly Attendance Report</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="salary_report">Salary Report</a>
        </li>
        <!-- <li class="nav-item dropdown hover-dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Dropdown</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="#">Link</a></li>
            <li><a class="dropdown-item" href="#">Another link</a></li>
            <li><a class="dropdown-item" href="#">A third link</a></li>
          </ul>
        </li> -->
      </ul>
      <form class="d-flex" method="POST" action="logout">
        <!-- <input class="form-control me-2" type="text" placeholder="Search"> -->
        <button class="btn btn-outline-danger" type="submit">Logout</button>
      </form>
    </div>
  </div>
</nav>
<style>
/* Show dropdown on hover */
@media (min-width: 576px) {
  .hover-dropdown:hover .dropdown-menu {
    display: block;
    margin-top: 0; /* Fix Bootstrap's default offset */
  }
}
</style>
