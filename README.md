# Audit App

## Description
This project is an audit management application that allows users to submit audits, manage them through an admin interface, and generate reports via email. It includes features for image uploads and optimizations to enhance performance.

## Features
- **Audit Submission**: Users can submit audits with various details and upload images.
- **Admin Interface**: Administrators can manage audits, view statistics, and delete audits if necessary.
- **Email Notifications**: Automatic email notifications are sent upon audit submission.
- **Image Optimization**: Images are optimized for size to improve loading times.

## Setup Instructions
1. **Clone the Repository**: 
   ```bash
   git clone <repository-url>
   cd audit-app
   ```

2. **Install Dependencies**: 
   If you are using Composer, run:
   ```bash
   composer install
   ```

3. **Database Setup**: 
   - Import the `setup/database.sql` file into your database to create the necessary tables.
   - Update the `src/config.php` file with your database connection details.

4. **Run the Application**: 
   - Start a local server (e.g., using XAMPP, MAMP, or PHP's built-in server).
   - Access the application via your web browser.

## Image Optimization Strategies
To optimize image loading times, consider implementing the following strategies:
1. Use the **ImageOptimizer** class to compress images before saving them to the server.
2. Implement **lazy loading** for images in the frontend to load images only when they are in the viewport.
3. Generate and serve smaller **thumbnail versions** of images for display purposes.

## Version Control with Git
To add this application to GitHub:
1. Initialize a Git repository in the project directory:
   ```bash
   git init
   ```

2. Add all files to the repository:
   ```bash
   git add .
   ```

3. Commit the changes:
   ```bash
   git commit -m "Initial commit"
   ```

4. Create a new repository on GitHub.

5. Link the local repository to the GitHub repository:
   ```bash
   git remote add origin <repository-url>
   ```

6. Push the changes to GitHub:
   ```bash
   git push -u origin master
   ```

## License
This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.