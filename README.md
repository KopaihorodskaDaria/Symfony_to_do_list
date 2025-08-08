# Symfony ToDo App
A task management web application built with **Symfony**.  
The project includes user authentication, email verification, password reset, task categories, deadline highlighting, drag & drop between statuses, task filtering, and more.

##Backend:
- PHP 8.2+
- Symfony 7.3 
- Doctrine ORM + Migrations
- SymfonyCasts ResetPasswordBundle
- SymfonyCasts VerifyEmailBundle
(Used SymfonyCasts ResetPasswordBundle and VerifyEmailBundle as a base, extending them with custom services for email verification and security checks, 
including token generation, custom HTML email templates, and login blocking for unverified accounts.)
- Symfony Mailer
- Docker / Docker Compose
- PHPUnit

##Frontend:
HTML5, CSS3, JavaScript
Twig templating engine
SortableJS (for drag & drop functionality)
Flatpickr (date picker)
Custom CSS styles

##Database:
MySQL

##Environment & Tools:
- Docker (for local development)
- MailHog (for testing email functionality)

##Key Features:
* User registration & login
* Email verification (unverified users cannot log in)
* Password reset functionality
* Create, edit, and delete tasks
* Task categorization (default & custom categories per user)
* Task filtering by category
* Deadline highlighting – overdue tasks are visually marked
* Drag & drop task reordering between statuses (To Do, In Progress, Done)
* Statistics page with task status diagram

##Running the Project
```bash
docker compose up -d
```

Visit: http://localhost:8080

##Testing
Run PHPUnit tests:
```bash
php bin/phpunit
```


