<div class="job-application-container">
    <form id="jobApplicationForm" class="job-application-form">
        <div class="form-header">
            <h2>Apply for <span id="jobTitle">Position</span></h2>
            <p class="job-ref">Job Reference: <span id="jobRef"></span></p>
        </div>

        <div class="form-group">
            <label for="fullName">Full Name *</label>
            <input type="text" id="fullName" name="fullName" required>
        </div>

        <div class="form-group">
            <label for="email">Email Address *</label>
            <input type="email" id="email" name="email" required>
        </div>

        <div class="form-group">
            <label for="phone">Phone Number *</label>
            <input type="tel" id="phone" name="phone" required>
        </div>

        <div class="form-group">
            <label for="currentPosition">Current Position</label>
            <input type="text" id="currentPosition" name="currentPosition">
        </div>

        <div class="form-group">
            <label for="linkedin">LinkedIn Profile</label>
            <input type="url" id="linkedin" name="linkedin" placeholder="https://linkedin.com/in/your-profile">
        </div>

        <div class="form-group">
            <label for="portfolio">Portfolio Website</label>
            <input type="url" id="portfolio" name="portfolio" placeholder="https://your-portfolio.com">
        </div>

        <div class="form-group">
            <label for="resume">Resume/CV *</label>
            <input type="file" id="resume" name="resume" accept=".pdf,.doc,.docx" required>
            <small>Accepted formats: PDF, DOC, DOCX (Max 5MB)</small>
        </div>

        <div class="form-group">
            <label for="coverLetter">Cover Letter</label>
            <textarea id="coverLetter" name="coverLetter" rows="5"></textarea>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" id="terms" name="terms" required>
                I agree to the privacy policy and terms of service *
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" id="submitApplication">Submit Application</button>
            <div class="application-status" id="applicationStatus"></div>
        </div>
    </form>
</div>

<style>
.job-application-container {
    max-width: 800px;
    margin: 2rem auto;
    padding: 2rem;
    background: #ffffff;
    border-radius: 10px;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
}

.job-application-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-header {
    text-align: center;
    margin-bottom: 2rem;
}

.form-header h2 {
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.job-ref {
    color: #7f8c8d;
    font-size: 0.9rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

label {
    font-weight: 500;
    color: #2c3e50;
}

input[type="text"],
input[type="email"],
input[type="tel"],
input[type="url"],
textarea {
    padding: 0.8rem;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
}

input[type="file"] {
    padding: 0.5rem;
    border: 1px dashed #ddd;
    border-radius: 5px;
    cursor: pointer;
}

input:focus,
textarea:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.1);
}

small {
    color: #7f8c8d;
    font-size: 0.8rem;
}

.form-actions {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    align-items: center;
}

button[type="submit"] {
    background-color: #3498db;
    color: white;
    padding: 1rem 2rem;
    border: none;
    border-radius: 5px;
    font-size: 1rem;
    cursor: pointer;
    transition: background-color 0.3s ease;
    width: 100%;
    max-width: 300px;
}

button[type="submit"]:hover {
    background-color: #2980b9;
}

.application-status {
    text-align: center;
    font-weight: 500;
}

.application-status.success {
    color: #27ae60;
}

.application-status.error {
    color: #e74c3c;
}

/* Responsive Design */
@media (max-width: 768px) {
    .job-application-container {
        margin: 1rem;
        padding: 1rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get job ID from URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    const jobId = urlParams.get('job_id');
    
    // Set job reference in the form
    document.getElementById('jobRef').textContent = `JOB-${jobId}`;

    // Get job title (you'll need to implement this based on your data structure)
    fetchJobTitle(jobId);
    
    

    const form = document.getElementById('jobApplicationForm');
    const statusDiv = document.getElementById('applicationStatus');

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Show loading state
        const submitButton = document.getElementById('submitApplication');
        submitButton.disabled = true;
        submitButton.textContent = 'Submitting...';
        statusDiv.className = 'application-status';
        statusDiv.textContent = 'Submitting your application...';

        try {
            // Create FormData object
            const formData = new FormData(form);
            formData.append('job_id', jobId);

            // Validate file size
            const resumeFile = document.getElementById('resume').files[0];
            if (resumeFile && resumeFile.size > 5 * 1024 * 1024) { // 5MB limit
                throw new Error('Resume file size must be less than 5MB');
            }

            // Send application
            const response = await fetch(`/wp-json/job-portal/v1/jobs/${jobId}/apply`, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin' // Include cookies
            });

//            if (!response.ok) {
//                throw new Error('Application submission failed');
//            }

            const result = await response.json();

            // Show success message
            statusDiv.className = 'application-status success';
            statusDiv.textContent = 'Application submitted successfully!';
            form.reset();

            // Redirect after success (optional)
            setTimeout(() => {
                window.location.href = '/application-success';
            }, 2000);

        } catch (error) {
            // Show error message
            statusDiv.className = 'application-status error';
            statusDiv.textContent = error.message || 'An error occurred. Please try again.';
        } finally {
            // Reset button state
            submitButton.disabled = false;
            submitButton.textContent = 'Submit Application';
        }
    });

    // File input validation
    document.getElementById('resume').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        
        if (file && !allowedTypes.includes(file.type)) {
            alert('Please upload a PDF or Word document');
            this.value = '';
        }
        
        if (file && file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB');
            this.value = '';
        }
    });
});

async function fetchJobTitle(jobId) {
    try {
        const response = await fetch(`/wp-json/job-portal/v1/jobs/${jobId}`);
        if (!response.ok) throw new Error('Failed to fetch job details');
        
        const jobData = await response.json();
        document.getElementById('jobTitle').textContent = jobData.title;
    } catch (error) {
        console.error('Error fetching job title:', error);
        document.getElementById('jobTitle').textContent = 'This Position';
    }
}

// Optional: Add form validation
function validateForm() {
    const email = document.getElementById('email').value;
    const phone = document.getElementById('phone').value;

    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert('Please enter a valid email address');
        return false;
    }

    // Phone validation
    const phoneRegex = /^\+?[\d\s-]{10,}$/;
    if (!phoneRegex.test(phone)) {
        alert('Please enter a valid phone number');
        return false;
    }

    return true;
}
</script>