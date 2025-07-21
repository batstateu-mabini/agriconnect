<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: ../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Agrivet Livestock Dashboard + AI</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f4fff4; }
    .card { border-radius: 1rem; }
    #output { white-space: pre-wrap; border: 1px solid #ccc; padding: 1rem; margin-top: 1rem; }
  </style>
</head>
<body>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h2 class="text-success">🐄 Agrivet Livestock Dashboard + AI</h2>
      <p class="mb-0">You are logged in as: <strong><?= htmlspecialchars($_SESSION['email']) ?></strong></p>
    </div>
    <a href="logout.php" class="btn btn-danger">Logout</a>
  </div>

  <!-- User Form -->
  <div class="card p-4 mb-4 shadow-sm">
    <h5>Submit a Livestock Concern</h5>
    <form id="caseForm">
      <div class="row g-3">
        <div class="col-md-4">
          <input type="text" name="animalName" class="form-control" placeholder="Animal Name" required>
        </div>
        <div class="col-md-4">
          <select name="type" class="form-select" required>
            <option value="">Animal Type</option>
            <option>Cow</option>
            <option>Pig</option>
            <option>Goat</option>
            <option>Chicken</option>
          </select>
        </div>
        <div class="col-md-4">
          <select name="status" class="form-select" required>
            <option value="">Health Status</option>
            <option>Healthy</option>
            <option>Underweight</option>
            <option>Sick</option>
            <option>Injured</option>
          </select>
        </div>
        <div class="col-12">
          <textarea name="message" class="form-control" placeholder="Explain your concern to the AI..." rows="3" required></textarea>
        </div>
        <div class="col-12 text-end">
          <button type="submit" class="btn btn-success">Submit Request</button>
        </div>
      </div>
    </form>
  </div>

  <!-- Table -->
  <div class="card p-4 shadow-sm">
    <h5>📋 My Submitted Requests</h5>
    <div class="table-responsive">
      <table class="table table-bordered table-striped mt-3">
        <thead class="table-success">
          <tr>
            <th>Animal Name</th>
            <th>Type</th>
            <th>Status</th>
            <th>Your Message</th>
            <th>AI Suggested Care</th>
          </tr>
        </thead>
        <tbody id="animalTable">
          <!-- Rows inserted here -->
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
  const form = document.getElementById("caseForm");
  const table = document.getElementById("animalTable");

  const API_KEY = "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"; // EXPOSED ONLY FOR TESTING

async function getAISuggestion(promptText) {
  try {
    const res = await fetch("https://api.groq.com/openai/v1/chat/completions", {
      method: "POST",
      headers: {
        Authorization: `Bearer ${API_KEY}`,
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        model: "llama3-70b-8192", // Groq's official supported model
        messages: [
                    {
                        role: "system",
                        content: "You are a livestock veterinary assistant. Respond in short, direct, practical suggestions in Tagalog. Avoid long explanations.",
                    },
                    { role: "user", content: promptText }
                    ],

        temperature: 0.7,
        max_tokens: 500,
        top_p: 1,
      }),
    });

    const data = await res.json();

    // DEBUG LOG (optional): Uncomment to inspect full response
    // console.log(JSON.stringify(data, null, 2));

    if (data.choices && data.choices.length > 0 && data.choices[0].message) {
      return data.choices[0].message.content;
    } else {
      return "Sorry, the AI could not generate a response.";
    }
  } catch (err) {
    console.error("Error from AI:", err);
    return "There was an error contacting the AI service.";
  }
}


  form.addEventListener("submit", async function (e) {
    e.preventDefault();

    const data = new FormData(form);
    const name = data.get("animalName");
    const type = data.get("type");
    const status = data.get("status");
    const message = data.get("message");

    const prompt = `You are a livestock veterinarian AI. An animal has been reported:\n\n` +
      `Animal: ${type}\nName: ${name}\nStatus: ${status}\nOwner Message: ${message}\n\n` +
      `Please suggest appropriate care, medication, feeding recommendation, and any important notes.`;

    const suggestion = await getAISuggestion(prompt);

    const row = document.createElement("tr");
    row.innerHTML = `
      <td>${name}</td>
      <td>${type}</td>
      <td>${status}</td>
      <td>${message}</td>
      <td>${suggestion}</td>
    `;
    table.appendChild(row);

    form.reset();
  });
</script>
</body>
</html>
