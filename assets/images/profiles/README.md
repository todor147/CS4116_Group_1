# Profile Images Directory

This directory stores user profile images for the EduCoach application.

## Default Image

The system expects a file named `default.jpg` in this directory to use as a fallback when users don't have their own profile image. If the default image isn't present, the system will automatically generate an avatar based on the user's name using the UI Avatars service.

## Requirements for Profile Images

- Images should be square (1:1 aspect ratio)
- Recommended size: 256x256 pixels
- Supported formats: JPG, PNG
- Maximum file size: 2MB

## Image Naming Convention

User profile images are named according to their user_id or a unique identifier specified in the database. 