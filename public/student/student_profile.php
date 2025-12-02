<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="Mohammad Sahragard">
    <title>Profile</title>
    <link rel="icon" type="image/png" href="../images/CHMSU.png">
    <link rel="stylesheet" href="../css/studentStyle.css">
    <script type="text/javascript" src="../script/app.js" defer></script>
    <script type="text/javascript" src="../script/student_logs_script.js" defer></script>
    <!-- import font icon (fontawesome) -->
    <script src="https://kit.fontawesome.com/b8b432d7d3.js" crossorigin="anonymous"></script>
</head>
<body>
    <?php include 'student_nav.php'; ?>   


        <div class="profile-card">
            <div class="profile-header"><!-- profile header section -->
                <div class="main-profile">
                    <div class="profile-image"></div>
                    <div class="profile-names">
                        <h1 class="username">Manuel G. Nigga</h1>
                        <small class="page-title">BSIT-4C</small>
                        <button class="edit-profile" style="background-color: #4f46e5; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">Change Profile</button>
                    </div>
                </div>
            </div>

            <div class="profile-body"><!-- profile body section -->
                <div class="info" style="display: flex; flex-direction: column; gap: 10px; margin-top:6em; margin-left: 1em;">
                    <b style="color:orange;">Email: </b>manuelnigga@gmail.com <br><b style="color:orange;">Phone: </b>09123456789<br><b style="color:orange;">School ID: </b>CMA12040300
                </div>
                <div class="logs-section">
                    <h3 class="logs-title">Recent Logs</h3>
                    <div class="logs-table-container">
                        <table class="logs-table">
                            <thead>
                                <tr>
                                    <th>Office</th>
                                    <th>Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>COE Office</td>
                                    <td>12:00 PM</td>
                                </tr>
                                <tr>
                                    <td>CIT Office</td>
                                    <td>1:00 PM</td>
                                </tr>
                                <tr>
                                    <td>CCS Office</td>
                                    <td>2:30 PM</td>
                                </tr>
                                <tr>
                                    <td>Library</td>
                                    <td>3:15 PM</td>
                                </tr>
                                <tr>
                                    <td>Registrar Office</td>
                                    <td>4:00 PM</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
            
            </div>
        </div>

    </div>
</body>
</html>

<style>
html {
    box-sizing: border-box;
    font-family: Arial, Helvetica, sans-serif;
}
*,
*::before,
*::after {
    box-sizing: inherit;
    margin: 0;
    padding: 0;
}

:root {
    --primary-bg: #171717;
    --secondary-bg: #262626;
    --accent-bg:rgb(44, 43, 58);

    --primary-color: #fff;
    --secondary-color: rgba(255,255,255, 70%);
    --accent-color: #818cf8;

    --border-color: rgba(255,255,255, 30%);
    
    --username-size: 32px;
    --title-size: 28px;
    --subtitle: 24px;
}
/*../images/profile.jpg*/

/* ---------- body element's */

.profile-card {
    height: 620px;
    background-color: var(--primary-bg);
    border-radius: 1em;
    border: 2px solid var(--accent-bg);
    display: grid;
    grid-template-rows: 220px auto;
}
/* ------ profile header section */
.profile-header {
    background: url('../images/homepage.png') center;
    background-size: cover;
    margin: 10px;
    border-radius: 30px 30px 0 0;
    position: relative;
}
    .main-profile {
        display: flex;
        align-items: center;
        position: absolute;
        inset: calc(100% - 123px) auto auto 70px;
    }
        .profile-image {
            width: 250px;
            height: 250px;
            background: url('../images/profile.jpg') center;
            background-size: cover;
            border-radius: 50%;
            border: 10px solid var(--primary-bg);
        }
        .profile-names {
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: var(--primary-color);
            background-color: var(--primary-bg);
            padding: 10px 30px;
            border-radius: 0 50px 50px 0;
            transform: translateX(-10px);
        }
            .page-title {
                color: var(--secondary-color);
            }

/* ------- profile body header */
.profile-body {
    display: grid;
    grid-template-columns: 500px auto;
    gap: 70px;
    padding: 40px;
    overflow: hidden;
}
.logs-section {
    margin-top: 2em;
    margin-left: 1em;
    display: flex;
    flex-direction: column;
    max-height: calc(623px - 100px - 92px - 132px);
    min-height: 0;
}
.logs-title {
    color: var(--primary-color);
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--accent-bg);
}
.logs-table-container {
    background-color: var(--secondary-bg);
    border-radius: 12px;
    border: 1px solid var(--border-color);
    overflow: hidden;
    max-height: 250px;
    overflow-y: auto;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}
.logs-table-container::-webkit-scrollbar {
    width: 8px;
}
.logs-table-container::-webkit-scrollbar-track {
    background: var(--primary-bg);
}
.logs-table-container::-webkit-scrollbar-thumb {
    background: var(--accent-bg);
    border-radius: 4px;
}
.logs-table-container::-webkit-scrollbar-thumb:hover {
    background: var(--accent-color);
}
.logs-table {
    width: 100%;
    border-collapse: collapse;
    color: var(--primary-color);
}
.logs-table thead {
    position: sticky;
    top: 0;
    background-color: var(--accent-bg);
    z-index: 10;
}
.logs-table th {
    padding: 1rem 1.25rem;
    text-align: left;
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--primary-color);
    border-bottom: 2px solid var(--border-color);
}
.logs-table tbody tr {
    transition: background-color 0.2s ease;
    border-bottom: 1px solid var(--border-color);
}
.logs-table tbody tr:hover {
    background-color: rgba(79, 70, 229, 0.1);
}
.logs-table tbody tr:last-child {
    border-bottom: none;
}
.logs-table td {
    padding: 0.9rem 1.25rem;
    color: var(--secondary-color);
    font-size: 0.95rem;
}
.logs-table td:first-child {
    font-weight: 500;
    color: var(--accent-color);
}
.account-info {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        grid-template-rows: 2fr 1fr;
        gap: 20px;
    }
    .profile-actions {
        display: grid;
        grid-template-rows: repeat(2, max-content) auto;
        gap: 10px;
        margin-top: 30px;
    }
    .profile-actions button {
        all: unset;
        padding: 10px;
        color: var(--primary-color);
        border: 2px solid var(--accent-bg);
        text-align: center;
        border-radius: 10px;
    }
        .profile-actions .follow {background-color: var(--accent-bg)}
    .bio {
        color: var(--primary-color);
        background-color: var(--secondary-bg);
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding: 10px;
        border-radius: 10px;
    }
        .bio-header {
            display: flex;
            gap: 10px;
            border-bottom: 1px solid var(--border-color);
            color: var(--secondary-color);
        }
        .data {
            grid-area: 1/1/2/3;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            color: var(--secondary-color);
            padding: 30px;
            text-align: center;
            border: 1px solid var(--border-color);
            border-radius: 15px;
        }
            .important-data {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .other-data {
                display: flex;
                justify-content: space-between;
                align-items: center;

                background-color: var(--secondary-bg);
                padding: 15px;
                border-radius: 10px;
            }
            .data-item .value {
                color: var(--accent-color);
            }
                .important-data .value {
                    font-size: var(--title-size);
                }
                .other-data .value {
                    font-size: var(--subtitle);
                }
        .social-media {
            grid-area: 2/1/3/3;
            background-color: var(--secondary-bg);
            color: var(--secondary-color);
            padding: 15px;
            border-radius: 10px;

            display: flex;
            align-items: center;
            gap: 15px;
        }
            .media-link {
                text-decoration: none;
                color: var(--accent-color);
                font-size: var(--subtitle);
            }
        .last-post {
            grid-area: 1/3/3/4;
            border: 1px solid var(--border-color);
            background-color: var(--secondary-bg);
            border-radius: 10px;
            padding: 10px;

            display: grid;
            grid-template-rows: 70% auto max-content;
            gap: 10px;
        }
            .post-cover {
                position: relative;
                background: url('/images/last-post.jpg') center;
                background-size: cover;
                border-radius: 5px;
            }
                .last-badge {
                    position: absolute;
                    inset: 3px 3px auto auto;
                    background-color: rgba(0,0,0, 70%);
                    color: var(--primary-color);
                    padding: 5px;
                    border-radius: 3px;
                }
            .post-title {
                color: var(--primary-color);
                font-size: 18px;
            }
            .post-CTA {
                all: unset;
                text-align: center;
                border: 1px solid var(--accent-color);
                color: var(--accent-color);
                border-radius: 5px;
                padding: 5px;
            }

/* ------------ media queries */
@media screen and (max-width: 950px) {
    .last-post {
        display: none;
    }
    .data, .social-media {
        grid-column: 1/4;
    }
    .username{
        font-size: 15px;
    }
    .info {
        display: flex;
        justify-content: center;
        align-items: center;
        text-align: center;
        font-size: 1em;
        font-weight: 600;
        color: var(--primary-color);
        background-color: var(--secondary-bg);
        padding: 10px;
        border-radius: 10px;
        margin-top: 12em !important;
        margin-left: 0em !important;
    }
    .profile-image{
        width:200px;
        height:200px;
    }
    .logs-section {
        display: none;
    }
    .logs-title {
        display: none;
    }
    .logs-table-container {
        display: none;
    }
    .logs-table {
        display: none;
    }
    .logs-table tbody tr {
        display: none;
    }
    .logs-table tbody tr:hover {
        display: none;
    }
    .logs-table tbody tr:last-child {
        display: none;
    }
    .logs-table td {
        display: none;
    }
    .logs-table td:first-child {
        display: none;
    }
    .logs-table td:last-child {
        display: none;
    }
}


@media screen and (max-width: 768px) {
    .profile-card {
        height: 100%;
        border-radius: 0;
    }
        .profile-header {
            border-radius: 0;
        }
            .main-profile {
                inset: calc(100% - 75px) auto auto 50%;
                transform: translateX(-50%);

                flex-direction: column;
                text-align: center;
            }
                .profile-names {transform: translateX(0)}
        .profile-body {
            grid-template-columns: 1fr;
            gap: 20px;
        }
            .profile-actions {
                grid-template-columns: repeat(2, 1fr);
                margin-top: 90px;
            }
                .bio {grid-column: 1/3;}

            .data {gap: 20px;}
}

</style>