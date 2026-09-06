# 💪 FIT MENTOR

A full-stack fitness management web application that helps users track workouts, monitor their BMI, and follow personalized diet and exercise plans.

## Overview

FIT MENTOR is designed to make fitness tracking simple and personalized. Users can register, log their progress, calculate their BMI, and receive workout and diet plans tailored to their goals — while admins get a dashboard to manage users and assign plans.

## Features

- 🔐 **User authentication** — secure registration and login system
- 📊 **BMI Calculator** — instant BMI calculation with health insights
- 🏋️ **Personalized plans** — workout and diet plans tailored to user goals
- 📈 **Progress tracking** — log and visualize fitness progress over time
- 🏆 **Ranking system** — see how you compare with other users
- 🛠️ **Admin dashboard** — manage users, assign and edit workout/diet plans
- 📱 **Responsive design** — built with Bootstrap for a clean experience across devices

## Tech Stack

- **Frontend:** HTML, CSS, JavaScript, Bootstrap
- **Backend:** PHP
- **Database:** MySQL

## Project Structure

fit-mentor/
├── admin/ # Admin-side pages (manage users, assign/edit plans)
├── assets/ # CSS, JS, and static assets
├── auth/ # Login, logout, registration
├── database/ # SQL schema
├── includes/ # Shared header/footer components
├── user/ # User-side pages (dashboard, BMI, plans, progress, ranking)
├── utils/ # Helper functions (e.g. password hashing)
├── index.html
└── index.php


## Setup & Installation

1. Clone the repository into your local server directory (e.g. XAMPP's `htdocs`):
```bash
   git clone https://github.com/julietsamsonraj2005/fit-mentor.git
```

2. Start Apache and MySQL via XAMPP.

3. Create a database in phpMyAdmin and import the schema:

4. Configure your database connection in the config file with your local MySQL credentials.

5. Visit `http://localhost/fit-mentor` in your browser.

## Usage

- **New users** can register an account, log in, and start tracking their fitness journey.
- **Admins** can log in through the admin panel to manage users and assign personalized workout/diet plans.

## Future Improvements

- Mobile app version
- Integration with wearable fitness trackers
- Social features — challenges and friend leaderboards
