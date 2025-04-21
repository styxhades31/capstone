<?php
require_once 'dbConnCode.php';

// Fetch all colleges from the colleges table
$collegesQuery = "SELECT * FROM colleges";
$collegesStmt = $conn->prepare($collegesQuery);
$collegesStmt->execute();
$collegesResult = $collegesStmt->get_result();
$colleges = [];
while ($college = $collegesResult->fetch_assoc()) {
    $colleges[] = $college;
}


// Check if the 'id' parameter is passed in the URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Fetch the record for the specified ID
    $query = "SELECT * FROM researcher_title_informations WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if the record exists
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
    } else {
        echo "Record not found!";
        exit;
    }

    // Fetch researchers involved in the selected title
    $researchersQuery = "
        SELECT id, first_name, last_name, middle_initial
        FROM researcher_involved
        WHERE researcher_title_id = ?
    ";
    $researchersStmt = $conn->prepare($researchersQuery);
    $researchersStmt->bind_param("i", $id);
    $researchersStmt->execute();
    $researchersResult = $researchersStmt->get_result();
    $researchers = [];
    while ($researcher = $researchersResult->fetch_assoc()) {
        $researchers[] = $researcher;
    }
     // Fetch certificates linked to this ID
     $certificatesQuery = "SELECT * FROM certificate_generated WHERE rti_id = ?";
     $certificatesStmt = $conn->prepare($certificatesQuery);
     $certificatesStmt->bind_param("i", $id);
     $certificatesStmt->execute();
     $certificatesResult = $certificatesStmt->get_result();
     $certificates = [];
     while ($certificate = $certificatesResult->fetch_assoc()) {
         $certificates[] = $certificate;
     }

    
} else {
    echo "Invalid request!";
    exit;
}
?>

<!-- HTML for displaying the edit form -->

<!DOCTYPE html>
<html lang="en">
<head>
	<title>Edit Research</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" type="text/css" href="./css/styles.css">
	<link rel="stylesheet" type="text/css" href="./css/piechart.css">
	<link rel="stylesheet" type="text/css" href="./css/admin-form.css" />
	<link href='https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css' rel='stylesheet'>

    
    <link rel="icon" type="image/x-icon" href="./img/reoclogo1.jpg">
    

    <script defer src="./js/table.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    
    <script>
        // Function to show the input field when 'Other' is selected for College/Institution
        function toggleOtherCollege() {
            var collegeSelect = document.getElementById("college");
            var otherCollegeInput = document.getElementById("otherCollegeInput");
            if (collegeSelect.value === "Other") {
                otherCollegeInput.style.display = "inline";
            } else {
                otherCollegeInput.style.display = "none";
            }
        }
    </script>

    

<style>
        .other-documents-container {
            display: flex;
            align-items: center;
        }
        .other-documents-container input[type="file"] {
            margin-right: 10px;
        }
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
          /* Add any additional styles for your header, footer, and navbar here */
          .header {
            background-color: #800000;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-content {
            display: flex;
            align-items: center;
        }
        .header h1 {
            margin: 0;
            margin-right: 20px;
        }
        .navbar {
            display: flex;
            gap: 10px;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            font-weight: bold;
            padding: 10px;
            transition: color 0.3s;
        }
        .navbar a:hover {
            color: #dc3545;
        }

        .logout-button {
            background-color: #aa3636;
            color: white;
            font-size:16px;
            padding: 5px 9px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            margin-left: 20px; /* Space between navbar and logout button */
            transition: background-color 0.3s;
        }
        .logout-button:hover {
            background-color: #c82333;
        }

        .main-content {
            flex: 1;
            padding: 20px;
        }
 
        .titles-container {
    position: fixed; /* Keep it fixed on the screen */
    top: 20px;
    right: 20px;
    background-color: rgba(0, 0, 0, 0.5); /* Optional: semi-transparent background */
    color: white;
    padding: 10px;
    border-radius: 5px;
    max-width: 300px; /* Optional: limits the width */
    box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2); /* Optional: adds a shadow */
    z-index: 10; /* Ensures it appears on top of other elements */
    overflow-y: auto; /* Ensure the list doesn't overflow the container */
}

.titles-container ul {
    list-style-type: none;
    padding-left: 0;
    margin: 0;
}

.titles-container li {
    margin-bottom: 5px;
}




















.limiter {
    width: 100%;
    margin: 0 auto;
  }
  
  .container-login100 {
    width: 100%;  
    min-height: 100vh;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    align-items: center;
    padding: 15px;
    background: #ebeeef;
  }


  .container-login1001 {
    width: 100%;  
    min-height: 100vh;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    align-items: center;
    padding: 15px;
    background: #ebeeef;
  }
  
  
  
  
  .wrap-login100 {
    width: 670px;
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    position: relative;
  }



  .wrap-login1001 {
    width: 1000px;
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    position: relative;
  }
  
  
  /*==================================================================
  [ Title form ]*/
  .login100-form-title {
    width: 100%;
    position: relative;
    z-index: 1;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
    flex-direction: column;
    align-items: center;
  
    background-repeat: no-repeat;
    background-size: cover;
    background-position: center;
  
    padding: 70px 15px 74px 15px;
  }
  
  .login100-form-title-1 {
    font-family: Poppins-Bold;
    font-size: 30px;
    color: #fff;
    text-transform: uppercase;
    line-height: 1.2;
    text-align: center;
  }
  
  .login100-form-title::before {
    content: "";
    display: block;
    position: absolute;
    z-index: -1;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    background: linear-gradient(to bottom, rgba(190, 41, 41, 0.562), rgba(51, 39, 39, 0.712));
  }




    /*==================================================================
  [ Title form1 ]*/
  .login100-form1-title {
    width: 100%;
    position: relative;
    z-index: 1;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
    flex-direction: column;
    align-items: center;
  
    background-repeat: no-repeat;
    background-size: cover;
    background-position: center;
  
    padding: 70px 15px 74px 15px;
  }
  
  .login100-form1-title-1 {
    font-family: Poppins-Bold;
    font-size: 30px;
    color: #fff;
    text-transform: uppercase;
    line-height: 1.2;
    text-align: center;
  }
  
  .login100-form1-title::before {
    content: "";
    display: block;
    position: absolute;
    z-index: -1;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    background: linear-gradient(to bottom, rgba(190, 41, 41, 0.562), rgba(51, 39, 39, 0.712));
  }
  
  
  
  /*==================================================================
  [ Form ]*/
  
  .login100-form {
    width: 100%;

    top: 20px;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    padding: 43px 88px 93px 190px;
  }

  .login100-form1 {
    width: 100%;
    position: relative;

    margin-top: 50px;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    padding: 20px 88px 93px 55px;
  }


  
  
  
  /*------------------------------------------------------------------
  [ Input ]*/
  
  .wrap-input100 {
    top: 20px;
    width: 100%;
    position: relative;
    border-bottom: 1px solid #b2b2b2;
  }

  .wrap-input200 {
    top: 20px;
    width: 60%;
    position: relative;
    border-bottom: 1px solid #0a8b5a00;
  }



  .wrap-input1001 {
    top: 20px;
    width: 100%;

    position: relative;
    border-radius: 5px;
  }


  .wrap-input100SN {
    width: 100%;
    position: relative;
    border-bottom: 1px solid #b2b2b2;
  }



  .wrap-input100FN {
    width: 100%;
    position: relative;
    border-bottom: 1px solid #b2b2b2;
  }


.opt{
  color: #660707;
}

  .wrap-input100MI {
    width: 100%;
    position: relative;
    border-bottom: 1px solid #b2b2b2;
  }
  
  .label-input100 {
    font-family: Poppins-Regular;
    font-size: 15px;
    color: #000000;
    line-height: 1.2;
    text-align: left;
  
    position: absolute;
    top: 14px;
    left: -105px;
    width: 80px;
  
  }
  



  .label-input200 {
    font-family: Poppins-Regular;
    font-size: 13px;
    color: #000000;
    line-height: 1.2;
    text-align: left;
  
    position: absolute;
  
    left: -105px;
    width: 270px;
  
  }





  /*---------------------------------------------*/
  .input100 {
    font-family: Poppins-Regular;
    font-size: 15px;
    color: #555555;
    line-height: 1.2;
    display: block;
    width: 100%;
    background: transparent;
    padding: 0 5px;
  }


  .input200 {
    position: relative;
    font-family: Poppins-Regular;
    font-size: 15px;
    color: #555555;
    line-height: 1.2;
    display: block;
    width: 100%;
    left: 180px;
    background: transparent;
    padding: 0 5px;
  }



.input1001 {
  position: relative;
  top: -7px;
  padding: 10px;
  border-radius: 5px;
  border: 1px solid #ccc;
  width: 100%;

  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  font-size: 16px;
  color: #333;

}


select.input1001 option:hover {
  background-color: #ff0000; 
  color: red; 
}


select.input1001 option:checked {
  background-color: #a83939; 
  color: rgb(255, 255, 255); 
}

.input1001 {
  background: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="gray"><path d="M7 10l5 5 5-5H7z"/></svg>') no-repeat right;
  background-size: 16px;
  height: 50px;
}




.login100-form-btn:hover {
  background-color: #a30707;
}


.login100-form-btn2:hover {
  background-color: #a30707;
}

.login100-form-btn1:hover {
  background-color: #a30707;
}


.login100-form1-btn:hover {
  background-color: #a30707;
}


.login100-form1-btn2:hover {
  background-color: #a30707;
}

.login100-form1-btn1:hover {
  background-color: #a30707;
}






  .inputsign {
    font-family: Poppins-Regular;
    font-size: 15px;
    color: #555555;
    line-height: 1.2;
    display: block;
    width: 100%;
    background: transparent;
    padding: 0 5px;
  }



  .inputsignSN {
    font-family: Poppins-Regular;
    font-size: 15px;
    color: #555555;
    line-height: 1.2;
    display: block;
    width: 100%;
    background: transparent;
    padding: 0 5px;
  }




  .name-fields {
    display: flex;
    justify-content: space-between;
}

.name-fields .wrap-input100 {
    width: 100%; 
}


.name-fields .wrap-input200 {
  width: 100%; 
}






.name-fields .wrap-input100SN {
    width: 35%; 
}

.name-fields .wrap-input100FN {
    width: 45%; 
}



.name-fields .wrap-input100MI {
    width: 8%; 
}

  
  .focus-input100 {
    position: absolute;
    display: block;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    pointer-events: none;
  }





  .focus-input200 {
    position: absolute;
    display: block;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    pointer-events: none;
  }







  .focus-input100FN {
    position: absolute;
    display: block;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    pointer-events: none;
  }
  
  .focus-input100SN {
    position: absolute;
    display: block;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    pointer-events: none;
  }
  
  .focus-input100MI {
    position: absolute;
    display: block;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    pointer-events: none;
  }
  





  .focus-input100::before {
    content: "";
    display: block;
    position: absolute;
    bottom: -1px;
    left: 0;
    width: 0;
    height: 1px;
  
    -webkit-transition: all 0.6s;
    -o-transition: all 0.6s;
    -moz-transition: all 0.6s;
    transition: all 0.6s;
  
    background-color: #751111;
  }
  
  

  .focus-input200::before {
    content: "";
    display: block;
    position: absolute;
    bottom: -1px;
    left: 0;
    width: 0;
    height: 1px;
  
    -webkit-transition: all 0.6s;
    -o-transition: all 0.6s;
    -moz-transition: all 0.6s;
    transition: all 0.6s;
  
    background-color: #751111;
  }
  
  

  .focus-input100FN::before {
    content: "";
    display: block;
    position: absolute;
    bottom: -1px;
    left: 0;
    width: 0;
    height: 1px;
  
    -webkit-transition: all 0.6s;
    -o-transition: all 0.6s;
    -moz-transition: all 0.6s;
    transition: all 0.6s;
  
    background-color: #751111;
  }
  

  .focus-input100SN::before {
    content: "";
    display: block;
    position: absolute;
    bottom: -1px;
    left: 0;
    width: 0;
    height: 1px;
  
    -webkit-transition: all 0.6s;
    -o-transition: all 0.6s;
    -moz-transition: all 0.6s;
    transition: all 0.6s;
  
    background-color: #751111;
  }
  

  .focus-input100MI::before {
    content: "";
    display: block;
    position: absolute;
    bottom: -1px;
    left: 0;
    width: 0;
    height: 1px;
  
    -webkit-transition: all 0.6s;
    -o-transition: all 0.6s;
    -moz-transition: all 0.6s;
    transition: all 0.6s;
  
    background-color: #751111;
  }
  


  /*---------------------------------------------*/
  input.input100 {
    height: 45px;
  }



  input.input200 {
    height: 45px;
  }




  input.inputsign {
    height: 45px;
  }
  
  
  input.inputsignSN {
    height: 45px;
  }
  


  input.inputsignFN {
    height: 45px;
  }
  
  input.inputsignMI {
    height: 45px;
  }
  

  
  .input100:focus + .focus-input100::before {
    width: 100%;
  }
  
  .has-val.input100 + .focus-input100::before {
    width: 100%;
  }
  

  .input100FN:focus + .focus-input100FN::before {
    width: 100%;
  }
  
  .has-val.input100FN + .focus-input100FN::before {
    width: 100%;
  }
  



  .input200:focus + .focus-input200::before {
    width: 100%;
  }
  
  .has-val.input200 + .focus-input200::before {
    width: 100%;
  }
  
















  .input100SN:focus + .focus-input100SN::before {
    width: 100%;
  }
  
  .has-val.input100SN + .focus-input100SN::before {
    width: 100%;
  }
  

  .input100MI:focus + .focus-input100MI::before {
    width: 100%;
  }
  
  .has-val.input100MI + .focus-input100MI::before {
    width: 100%;
  }
  








  .inputsign:focus + .focus-input100::before {
    width: 100%;
  }
  
  .has-val.inputsign + .focus-input100::before {
    width: 100%;
  }




  .inputsign:focus + .focus-input200::before {
    width: 100%;
  }
  
  .has-val.inputsign + .focus-input200::before {
    width: 100%;
  }












  .inputsignSN:focus + .focus-input100::before {
    width: 100%;
  }
  
  .has-val.inputsignSN + .focus-input100::before {
    width: 100%;
  }











  /*==================================================================
  [ Restyle Checkbox ]*/
  
  .input-checkbox100 {
    display: none;
  }
  
  .label-checkbox100 {
    font-family: Poppins-Regular;
    font-size: 13px;
    color: #999999;
    line-height: 1.4;
  
    display: block;
    position: relative;
    padding-left: 26px;
    cursor: pointer;
  }
  
  .label-checkbox100::before {
    content: "\f00c";
    font-family: FontAwesome;
    font-size: 13px;
    color: transparent;
  
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    justify-content: center;
    align-items: center;
    position: absolute;
    width: 18px;
    height: 18px;
    border-radius: 2px;
    background: #fff;
    border: 1px solid #e6e6e6;
    left: 0;
    top: 50%;
    -webkit-transform: translateY(-50%);
    -moz-transform: translateY(-50%);
    -ms-transform: translateY(-50%);
    -o-transform: translateY(-50%);
    transform: translateY(-50%);
  }
  
  .input-checkbox100:checked + .label-checkbox100::before {
    color: #57b846;
  }
  
  /*------------------------------------------------------------------
  [ Button ]*/
  .container-login100-form-btn {
    
    position: relative;
    margin-left: 350px;
    margin-top: 100px;
    width: 100%;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
  }




  .container-login100-form-btn2 {
    width: 100%;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
  }

  .container-login1001-form-btn {
    width: 100%;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
  }




  .container-login1001-form-btn2 {
    width: 100%;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
  }





  
  .login100-form-btn {
     position: relative;
right: 20px;
    display: flex;
    gap: 10px;
    justify-content: center;
    align-items: center;
    padding: 20 20px;
    min-width: 160px;
    height: 50px;
    background-color: #751111;
    border-radius: 25px;
  
    font-family: Poppins-Regular;
    font-size: 16px;
    color: #fff;
    line-height: 1.2;
  
    -webkit-transition: all 0.4s;
    -o-transition: all 0.4s;
    -moz-transition: all 0.4s;
    transition: all 0.4s;
  }



  .login100-form-btn {
    position: relative;
   top:-20px;
   right: 222px;
   display: flex;
   gap: 10px;
   justify-content: center;
   align-items: center;
   padding: 20px 20px;
   min-width: 160px;
   height: 50px;
   background-color: #751111;
   border-radius: 25px;
 
   font-family: Poppins-Regular;
   font-size: 16px;
   color: #fff;
   line-height: 1.2;
 
   -webkit-transition: all 0.4s;
   -o-transition: all 0.4s;
   -moz-transition: all 0.4s;
   transition: all 0.4s;
 }
 
  
  .login100-form-btn:hover {
    background-color: #a30707;
  }


  .login100-form1-btn:hover {
    background-color: #a30707;
  }




  
  .login100-form-btn2 {
    position: relative;
left:65px;
   display: flex;
   gap: 10px;
   justify-content: center;
   align-items: center;
   padding: 20 20px;
   min-width: 160px;
   height: 50px;
   background-color: #751111;
   border-radius: 25px;
 
   font-family: Poppins-Regular;
   font-size: 16px;
   color: #fff;
   line-height: 1.2;
 
   -webkit-transition: all 0.4s;
   -o-transition: all 0.4s;
   -moz-transition: all 0.4s;
   transition: all 0.4s;
 }
 
 

 .login100-form1-btn2 {
  position: relative;
left:65px;
 display: flex;
 gap: 10px;
 justify-content: center;
 align-items: center;
 padding: 20 20px;
 min-width: 160px;
 height: 50px;
 background-color: #751111;
 border-radius: 25px;

 font-family: Poppins-Regular;
 font-size: 16px;
 color: #fff;
 line-height: 1.2;

 -webkit-transition: all 0.4s;
 -o-transition: all 0.4s;
 -moz-transition: all 0.4s;
 transition: all 0.4s;
}










  .container-login100-form-btn1 {
    width: 100%;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
  }
  

  .container-login1001-form-btn1 {
    width: 100%;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
  }
  



  .login100-form-btn1 {
    position: relative;
  margin-left: 250px;
  margin-top: 90px;
   display: flex;
   gap: 10px;
   justify-content: center;
   align-items: center;
   padding: 20px;
   min-width: 160px;
   height: 50px;
   background-color: #751111;
   border-radius: 25px;
 
   font-family: Poppins-Regular;
   font-size: 16px;
   color: #fff;
   line-height: 1.2;
 
   -webkit-transition: all 0.4s;
   -o-transition: all 0.4s;
   -moz-transition: all 0.4s;
   transition: all 0.4s;
 }

 .login100-form-btn1 {
  position: relative;
margin-left: 250px;
margin-top: 90px;
 display: flex;
 gap: 10px;
 justify-content: center;
 align-items: center;
 padding: 20px;
 min-width: 160px;
 height: 50px;
 background-color: #751111;
 border-radius: 25px;

 font-family: Poppins-Regular;
 font-size: 16px;
 color: #fff;
 line-height: 1.2;

 -webkit-transition: all 0.4s;
 -o-transition: all 0.4s;
 -moz-transition: all 0.4s;
 transition: all 0.4s;
}



   
 
  
  .login100-form-btn1:hover {
    background-color: #a30707;
  }
  
  .login100-form-btn2:hover {
    background-color: #a30707;
  }


    
  .login100-form1-btn1:hover {
    background-color: #a30707;
  }
  
  .login100-form1-btn2:hover {
    background-color: #a30707;
  }
  
  
.move{
    position: relative;
    left: 100px;
}



.addbtn{
    position: relative;
    left: 320px;
    padding: 6px 6px ;
            font-size: 11px;
            border: none;
            border-radius: 10px;
            background-color:  #aa3636;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s;
         margin-top: 20px;

}



.addbtn:hover{
    background-color: #802c2c;
}




.cobtn{
  position: relative;

    padding: 6px 6px ;
            font-size: 11px;
            border: none;
            border-radius: 10px;
            background-color:  #aa3636;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s;
         margin-top: 20px;
}


.cobtn:hover{
  background-color: #802c2c;
}

.formm{
    position: relative;
    left: 600px;
    margin-top:50px;
    width: fit-content;
    padding: 30px;
    border-style: solid;
    border-radius: 10px;
    border-color:  #aa3636;
}


.updatebtn{
			padding: 10px 20px; font-size: 16px; cursor: pointer;    background-color: #aa3636; /* Blue color */
          color: white; /* Text color */
          border: none; /* Remove border */
          border-radius: 10px; /* Rounded corners */
          padding: 5px 9px; /* Button size */
          font-size: 16px; /* Font size */
		  transition: background-color 0.3s;
          position: relative;
          left: 280px;
          margin-top:20px;

	
	}

	.updatebtn:hover {
		background-color: #802c2c;

	}

    </style>
</head>



    <!-- Header Section -->
        
<header>
  <a href="#" class="brand">
    <img src="img/logos.png" class="logo">
    <span class="reoc">Research Ethics Oversite Committee Portal</span>
  </a>

  <div class="menu-btn">
    <div class="navigation">
      <div class="navigation-items">
      <a href="adminHome.php">Home</a>
      <a href="admin_applicationforms.php">Application Forms</a>
      <a href="Account.php">Account Verifications</a>
      <a href="admin_activateSuperuser.php">Super Users</a>
   
   
       

        <!-- Logout Button -->
        <form method="POST" action="researcherHome.php" style="display: inline;">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
          <button type="submit" name="logout" class="logout-button">Logout</button>
        </form>
      </div>
    </div>
  </div>
  </header>
<body>


  
    <form action="updateResearch.php" method="POST" enctype="multipart/form-data" class="formm">
    <h2>Edit Researcher Application</h2>
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id']); ?>">

        <!-- Study Protocol Title -->
        <label for="study_protocol_title">Study Protocol Title:</label>
        <input type="text" name="study_protocol_title" value="<?php echo htmlspecialchars($row['study_protocol_title']); ?>" required><br>

        <!-- College/Institution Dropdown -->
        <!-- College/Institution Dropdown -->
<label for="college">College/Institution:</label>
<select name="college" id="college" onchange="toggleOtherCollege()" required>
    <?php 
    // Check if the user has selected a college and display that value as selected
    if ($row['college'] == "") {
        echo '<option value="">Select College/Institution</option>';
    }
    ?>
    <?php foreach ($colleges as $college): ?>
        <option value="<?php echo htmlspecialchars($college['college_name_and_color']); ?>"
            <?php echo ($row['college'] == $college['college_name_and_color']) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($college['college_name_and_color']); ?>
        </option>
    <?php endforeach; ?>
    <option value="Other" <?php echo ($row['college'] == 'Other') ? 'selected' : ''; ?>>Other</option>
</select><br>

<!-- Input for 'Other' College/Institution -->
<input type="text" id="otherCollegeInput" name="college_other" placeholder="Enter College/Institution" style="display: <?php echo ($row['college'] == 'Other') ? 'inline' : 'none'; ?>;"><br>

        <!-- Research Category Dropdown -->
        <label for="research_category">Research Category:</label>
        <select name="research_category" required>
            <option value="WMSU Undergraduate Thesis - 300.00" <?php echo ($row['research_category'] == 'WMSU Undergraduate Thesis - 300.00') ? 'selected' : ''; ?>>WMSU Undergraduate Thesis - 300.00</option>
            <option value="WMSU Master's Thesis - 700.00" <?php echo ($row['research_category'] == "WMSU Master's Thesis - 700.00") ? 'selected' : ''; ?>>WMSU Master's Thesis - 700.00</option>>
            <option value="WMSU Dissertation - 1,500.00" <?php echo ($row['research_category'] == 'WMSU Dissertation - 1,500.00') ? 'selected' : ''; ?>>WMSU Dissertation - 1,500.00</option>
            <option value="WMSU Institutionally Funded Research - 2,000.00" <?php echo ($row['research_category'] == 'WMSU Institutionally Funded Research - 2,000.00') ? 'selected' : ''; ?>>WMSU Institutionally Funded Research - 2,000.00</option>
            <option value="Externally Funded Research / Other Institution - 3,000.00" <?php echo ($row['research_category'] == 'Externally Funded Research / Other Institution - 3,000.00') ? 'selected' : ''; ?>>Externally Funded Research / Other Institution - 3,000.00</option>
        </select><br>

        <!-- Adviser Name -->
        <label for="adviser_name">Adviser Name:</label>
        <input type="text" name="adviser_name" value="<?php echo htmlspecialchars($row['adviser_name']); ?>" required><br>

        <!-- Editable Researchers Involved -->
        <label for="researchers_involved">Researchers Involved:</label>
        <div>
            <?php if (count($researchers) > 0): ?>
                <?php foreach ($researchers as $researcher): ?>
                    <div>
                        <label>First Name:</label>
                        <input type="text" name="researcher_first_name[<?php echo $researcher['id']; ?>]" value="<?php echo htmlspecialchars($researcher['first_name']); ?>"><br>
                        <label>Middle Initial:</label>
                        <input type="text" name="researcher_middle_initial[<?php echo $researcher['id']; ?>]" value="<?php echo htmlspecialchars($researcher['middle_initial']); ?>"><br>
                        <label>Last Name:</label>
                        <input type="text" name="researcher_last_name[<?php echo $researcher['id']; ?>]" value="<?php echo htmlspecialchars($researcher['last_name']); ?>"><br>
                        
                      
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No researchers found.</p>
            <?php endif; ?>
        </div><br>

<!-- Certificates Section -->
<h3>Certificates</h3>
<?php if (count($certificates) > 0): ?>
    <?php foreach ($certificates as $certificate): ?>
        <div>
            <p>
                Certificate File Name: <?php echo htmlspecialchars($certificate['file_path']); ?>
                <a href="<?php echo 'http://localhost/REOC/pdfs/' . htmlspecialchars(basename($certificate['file_path'])); ?>" download>
                    Download
                </a>
            </p>
            <label for="replace_certificate_<?php echo $certificate['id']; ?>">Replace File:</label>
            <input type="file" name="replace_certificate[<?php echo $certificate['id']; ?>]" id="replace_certificate_<?php echo $certificate['id']; ?>"><br>
            <input type="hidden" name="current_file_path[<?php echo $certificate['id']; ?>]" value="<?php echo htmlspecialchars($certificate['file_path']); ?>">

            <!-- Status Dropdown -->
            <label for="status_<?php echo $certificate['id']; ?>">Status:</label>
            <select name="certificate_status[<?php echo $certificate['id']; ?>]" id="status_<?php echo $certificate['id']; ?>">
                <option value="Hide" <?php echo ($certificate['status'] === 'Hide') ? 'selected' : ''; ?>>Hide</option>
                <option value="Show" <?php echo ($certificate['status'] === 'Show') ? 'selected' : ''; ?>>Show</option>
            </select>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p>No certificates found for this application.</p>
<?php endif; ?>



        <!-- Submit button -->
        <input type="submit" value="Update" class="updatebtn">
    </form>
    
<footer class="footer">
		<div class="owl-carousel">
	  
		  <a href="#" class="gallery__photo">
			<img src="img/wmsu55.jpg" alt="" />
		 
		  </a>
		  <a href="#" class="gallery__photo">
			<img src="img/wmsu11.jpg" alt="" />
		  
		  </a>
		  <a href="#" class="gallery__photo">
			<img src="img/reoc11.jpg" alt="" />
		   
		  </a>
		  <a href="#" class="gallery__photo">
			<img src="img/wmsu22.jpg" alt="" />
		  
		  </a>
		  <a href="#" class="gallery__photo">
			<img src="img/reoc22.jpg" alt="" />
		   
		  </a>
		  <a href="#" class="gallery__photo">
			<img src="img/wmsu44.jpg" alt="" />
		   
		  </a>
	  
		</div>
		<div class="footer__redes">
		  <ul class="footer__redes-wrapper">
			<li>
			  <a href="#" class="footer__link">
				<i class=""></i>
				Normal Road, Baliwasan, Z.C.
			  </a>
			</li>
			<li>
			  <a href="#" class="footer__link">
				<i class=""></i>
				09112464566
			  </a>
			</li>
			<li>
			  <a href="#" class="footer__link">
				<i class=""></i>
				wmsureoc@gmail.com
			  </a>
			</li>
			<li>
			  <a href="#" class="footer__link">
				<i class="fab fa-phone-alt"></i>
				
			  </a>
			</li>
		  </ul>
		</div>
		<div class="separador"></div>
		<p class="footer__texto">RESEARCH ETHICS OVERSITE COMMITTEE - WMSU</p>
	  </footer>
	

	  
   
  
  
  
  <!-- partial -->

  
	<script src='https://code.jquery.com/jquery-3.6.0.min.js'></script>
	<script src='https://unpkg.com/feather-icons'></script>
	  <script src="./js/main.js"></script>
	  <script src="./js/swiper.js"></script>
	  <script src="./js/footer.js"></script>
	  <script src="./js/faq.js"></script>
	

	<script src="./js/fonts.js"></script>
  
  
</body>
</html>
