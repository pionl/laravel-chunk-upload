name: Bug Report
description: Create a report to help us improve the chunk upload package
title: "[Bug]: "
labels: ["bug"]
body:
  - type: markdown
    attributes:
      value: |
        Thanks for taking the time to report an issue! Please fill out the form below as completely as possible.

  - type: checkboxes
    id: checklist
    attributes:
      label: Pre-submission Checklist
      options:
        - label: I have searched existing issues to make sure this isn't a duplicate.
        - label: I have enabled logging via `CHUNK_UPLOAD_LOGGING_ENABLED=true` in `.env` and cleared config (`php artisan config:cache`).

  - type: textarea
    id: steps
    attributes:
      label: Steps to reproduce
      description: How can we reproduce this problem?
      placeholder: |
        1. Go to '...'
        2. Click on '....'
        3. Scroll down to '....'
        4. See error
    validations:
      required: true

  - type: textarea
    id: expected
    attributes:
      label: Expected result
      description: What did you expect to happen?
    validations:
      required: true

  - type: textarea
    id: actual
    attributes:
      label: What do you get instead?
      description: What actually happened? Include error messages or screenshots if applicable.
    validations:
      required: true

  - type: input
    id: package-version
    attributes:
      label: Package version
      placeholder: e.g., v1.5.0
    validations:
      required: true

  - type: dropdown
    id: laravel-version
    attributes:
      label: Laravel Framework version
      options:
        - "master"
        - "13.x" 
        - "12.x"
        - "11.x"
        - "10.x"
        - "9.x"
        - "8.x"
        - Other
    validations:
      required: true

  - type: dropdown
    id: php-version
    attributes:
      label: PHP version
      options:
        - "8.5"
        - "8.4"
        - "8.3"
        - "8.2"
        - "8.1"
        - "8.0"
        - Other
    validations:
      required: true

  - type: input
    id: os
    attributes:
      label: Operating system
      placeholder: e.g., Ubuntu 22.04, macOS Sonoma, Windows 11

  - type: textarea
    id: logs
    attributes:
      label: Logs
      description: Paste relevant logs below.
      render: shell
    validations:
      required: false
